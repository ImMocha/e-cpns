<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LeaderboardAccess;
use App\Enums\TransactionStatus;
use App\Models\Exam;
use App\Models\Material;
use App\Models\Package;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserPackage;
use App\PackagePayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PackagePaymentTest extends TestCase
{
    use RefreshDatabase;

    private const SNAP = 'https://app.sandbox.midtrans.com/snap/v1/transactions';

    private const PAYMENT_URL = 'https://app.sandbox.midtrans.com/snap/v3/redirection/test-token';

    private function configureGateway(): void
    {
        config(['services.midtrans.server_key' => 'sandbox-test-key', 'services.midtrans.is_production' => false]);
        Http::preventStrayRequests();
    }

    /** @param array<string, mixed> $attributes */
    private function package(array $attributes = []): Package
    {
        $package = Package::factory()->create($attributes);
        $package->materials()->attach(Material::factory()->premium()->video()->create());

        return $package;
    }

    private function order(?User $user = null, ?Package $package = null): Transaction
    {
        return app(PackagePayment::class)->checkout($user ?? User::factory()->create(), $package ?? $this->package());
    }

    /** @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function providerPayload(Transaction $transaction, array $overrides = []): array
    {
        return array_replace([
            'order_id' => $transaction->order_id, 'gross_amount' => $transaction->amount.'.00',
            'status_code' => '200', 'transaction_id' => 'midtrans-'.$transaction->id,
            'transaction_status' => 'settlement', 'payment_type' => 'bank_transfer', 'currency' => 'IDR',
        ], $overrides);
    }

    /** @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function signed(array $payload): array
    {
        $payload['signature_key'] = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].'sandbox-test-key');

        return $payload;
    }

    private function statusUrl(Transaction $transaction): string
    {
        return 'https://api.sandbox.midtrans.com/v2/'.$transaction->order_id.'/status';
    }

    public function test_checkout_uses_server_price_and_reuses_pending_order_and_payment_link(): void
    {
        $this->freezeTime();
        $this->configureGateway();
        Http::fake([self::SNAP => Http::response(['token' => 'test-token', 'redirect_url' => self::PAYMENT_URL], 201)]);
        $user = User::factory()->create();
        $package = $this->package(['price' => 75000, 'duration_days' => 14, 'tryout_quota' => 3]);

        $this->actingAs($user)->get(route('checkout', ['package' => $package->id]))->assertSee('Konfirmasi paketmu');
        $this->post(route('checkout.store'), ['package_id' => $package->id, 'amount' => 1, 'status' => 'paid'])->assertSessionHasNoErrors();
        $transaction = Transaction::sole();
        $this->post(route('checkout.store'), ['package_id' => $package->id])->assertRedirect(route('transactions.show', $transaction));
        $this->post(route('transactions.pay', $transaction))->assertRedirect(self::PAYMENT_URL);

        $this->assertSame(75000, $transaction->amount);
        $this->assertSame(TransactionStatus::Pending, $transaction->status);
        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseCount('user_packages', 0);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === self::SNAP
            && $request['transaction_details']['gross_amount'] === 75000
            && $request['transaction_details']['order_id'] === $transaction->order_id
            && $request['item_details'][0]['price'] === 75000
            && $request['customer_details']['email'] === $user->email
            && $request['callbacks']['finish'] === route('transactions.show', $transaction)
            && $request['expiry']['start_time'] === $transaction->created_at->copy()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s O')
            && $request->hasHeader('Authorization', 'Basic '.base64_encode('sandbox-test-key:')));
        $this->get(route('transactions.show', $transaction))->assertSee('Bayar di Midtrans')->assertSee('Belum dipilih');
        $this->get(route('transactions.index'))->assertSee($transaction->order_id);
    }

    public function test_free_package_activates_without_calling_midtrans(): void
    {
        $this->freezeTime();
        config(['services.midtrans.server_key' => '']);
        Http::preventStrayRequests();
        $user = User::factory()->create();
        $package = $this->package(['price' => 0, 'duration_days' => null, 'tryout_quota' => null, 'leaderboard_access' => 'local']);

        $this->actingAs($user)->post(route('checkout.store'), ['package_id' => $package->id])->assertSessionHasNoErrors();
        $transaction = Transaction::sole();

        $this->assertSame('free', $transaction->provider);
        $this->assertSame(TransactionStatus::Paid, $transaction->status);
        $this->assertNotNull($transaction->paid_at);
        $this->assertDatabaseHas('user_packages', ['id' => $transaction->user_package_id, 'price' => 0, 'expires_at' => null, 'tryout_quota' => null]);
        $this->assertSame(LeaderboardAccess::Local, $user->leaderboardAccess());
        $this->post(route('checkout.store'), ['package_id' => $package->id])->assertInvalid(['package_id' => 'Kamu sudah memiliki paket ini']);
        $this->assertDatabaseCount('user_packages', 1);
        Http::assertNothingSent();
    }

    public function test_unconfigured_payment_is_unavailable_and_does_not_create_order(): void
    {
        config(['services.midtrans.server_key' => '']);
        Http::preventStrayRequests();
        $user = User::factory()->create();
        $package = $this->package();

        $this->actingAs($user)->get(route('checkout', ['package' => $package->id]))->assertSee('Pembayaran belum tersedia')->assertDontSee('Lanjut ke pembayaran');
        $this->post(route('checkout.store'), ['package_id' => $package->id])->assertInvalid(['package_id' => 'Pembayaran belum tersedia']);

        $this->assertDatabaseCount('transactions', 0);
        Http::assertNothingSent();
    }

    public function test_checkout_requires_active_verified_participant_and_valid_package(): void
    {
        $this->configureGateway();
        $package = $this->package();

        $this->post(route('checkout.store'), ['package_id' => $package->id])->assertRedirect(route('login'));
        $this->actingAs(User::factory()->admin()->create())->post(route('checkout.store'), ['package_id' => $package->id])->assertForbidden();
        $this->get(route('checkout', ['package' => $package->id]))->assertForbidden();
        $this->actingAs(User::factory()->unverified()->create())->post(route('checkout.store'), ['package_id' => $package->id])->assertRedirect(route('verification.notice'));
        $this->actingAs(User::factory()->create(['status' => 'inactive']))->post(route('checkout.store'), ['package_id' => $package->id])->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('checkout'))->assertRedirect(route('packages.index'));
        $this->get(route('checkout', ['package' => ['bad']]))->assertNotFound();
        $this->post(route('checkout.store'), [])->assertInvalid(['package_id']);
        $package->update(['is_active' => false]);
        $this->post(route('checkout.store'), ['package_id' => $package->id])->assertInvalid(['package_id']);
        $this->get(route('checkout', ['package' => $package->id]))->assertNotFound();

        $this->assertDatabaseCount('transactions', 0);
        Http::assertNothingSent();
    }

    public function test_unavailable_or_empty_package_cannot_be_purchased(): void
    {
        $this->configureGateway();
        $user = User::factory()->create();
        $package = $this->package();
        $package->materials()->first()->delete();
        $empty = Package::factory()->create();

        $this->actingAs($user)->post(route('checkout.store'), ['package_id' => $package->id])->assertInvalid(['package_id' => 'Konten paket sedang tidak tersedia']);
        $this->post(route('checkout.store'), ['package_id' => $empty->id])->assertInvalid(['package_id']);

        $this->assertDatabaseCount('transactions', 0);
        Http::assertNothingSent();
    }

    public function test_timeout_keeps_pending_order_and_retry_uses_same_order_id(): void
    {
        $this->configureGateway();
        Http::fake([self::SNAP => Http::sequence()->pushFailedConnection()->push(['redirect_url' => self::PAYMENT_URL], 201)]);
        $user = User::factory()->create();
        $package = $this->package();

        $this->actingAs($user)->post(route('checkout.store'), ['package_id' => $package->id])->assertSessionHasErrors('payment');
        $transaction = Transaction::sole();
        $this->assertSame(TransactionStatus::Pending, $transaction->status);
        $this->assertNull($transaction->payment_url);
        $this->post(route('transactions.pay', $transaction))->assertRedirect(self::PAYMENT_URL);

        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseCount('user_packages', 0);
        Http::assertSent(fn (Request $request): bool => $request['transaction_details']['order_id'] === $transaction->order_id);
    }

    public function test_gateway_failure_does_not_leak_provider_body_or_secret(): void
    {
        $this->configureGateway();
        Http::fake([self::SNAP => Http::response(['error_messages' => ['sandbox-test-key sensitive detail']], 401)]);
        $user = User::factory()->create();
        $package = $this->package();

        $this->actingAs($user)->post(route('checkout.store'), ['package_id' => $package->id])->assertSessionHasErrors('payment');
        $this->get(route('transactions.show', Transaction::sole()))->assertDontSee('sandbox-test-key')->assertDontSee('sensitive detail');

        $this->assertDatabaseCount('user_packages', 0);
        Http::assertSentCount(1);
    }

    public function test_signed_notification_activates_snapshot_once_and_starts_duration_on_payment(): void
    {
        $this->freezeTime();
        $this->configureGateway();
        $transaction = $this->order(package: $this->package(['price' => 75000, 'duration_days' => 14, 'tryout_quota' => 3, 'leaderboard_access' => 'national']));
        $materialIds = array_column($transaction->package_snapshot['materials'], 'id');
        $package = $transaction->package;
        $package->update(['name' => 'Paket Baru', 'price' => 200000, 'duration_days' => 90, 'tryout_quota' => 99, 'leaderboard_access' => 'none', 'is_active' => false]);
        $package->materials()->sync([Material::factory()->premium()->video()->create()->id]);
        $package->delete();
        $this->travel(2)->hours();
        $paidAt = now()->startOfSecond();
        $status = $this->providerPayload($transaction);
        Http::fake([$this->statusUrl($transaction) => Http::response($status)]);

        $this->postJson(route('payments.midtrans.notification'), $this->signed($status))->assertOk();
        $this->postJson(route('payments.midtrans.notification'), $this->signed($status))->assertOk();
        $transaction->refresh();
        $grant = UserPackage::sole();

        $this->assertSame(TransactionStatus::Paid, $transaction->status);
        $this->assertSame($grant->id, $transaction->user_package_id);
        $this->assertSame(75000, $grant->price);
        $this->assertSame(3, $grant->tryout_quota);
        $this->assertSame(LeaderboardAccess::National, $grant->leaderboard_access);
        $this->assertSame($transaction->package_snapshot['name'], $grant->name);
        $this->assertTrue($grant->starts_at->equalTo($paidAt));
        $this->assertTrue($grant->expires_at->equalTo($paidAt->copy()->addDays(14)));
        $this->assertSame($materialIds, $grant->materials()->pluck('materials.id')->all());
        $this->assertNull($grant->granted_by);
        $this->actingAs($transaction->user)->get(route('materials.show', $materialIds[0]))->assertOk();
        Http::assertSentCount(2);
    }

    public function test_forged_or_malformed_notification_is_rejected_before_api_or_activation(): void
    {
        $this->configureGateway();
        $transaction = $this->order();
        $payload = $this->signed($this->providerPayload($transaction));
        $payload['gross_amount'] = '1.00';

        $this->postJson(route('payments.midtrans.notification'), $payload)->assertForbidden();
        $this->postJson(route('payments.midtrans.notification'), ['order_id' => ['bad']])->assertForbidden();

        $this->assertDatabaseCount('user_packages', 0);
        $this->assertSame(TransactionStatus::Pending, $transaction->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_signed_notification_uses_authoritative_status_instead_of_callback_claim(): void
    {
        $this->configureGateway();
        $transaction = $this->order();
        Http::fake([$this->statusUrl($transaction) => Http::response($this->providerPayload($transaction, ['transaction_status' => 'pending']))]);

        $this->postJson(route('payments.midtrans.notification'), $this->signed($this->providerPayload($transaction)))->assertOk();

        $this->assertSame(TransactionStatus::Pending, $transaction->fresh()->status);
        $this->assertDatabaseCount('user_packages', 0);
        Http::assertSentCount(1);
    }

    /** @return array<string, array{array<string, mixed>}> */
    public static function mismatchedPayments(): array
    {
        return [
            'amount' => [['gross_amount' => '1.00']],
            'fraction' => [['gross_amount' => '150000.50']],
            'order' => [['order_id' => 'another-order']],
            'currency' => [['currency' => 'USD']],
            'reference missing' => [['transaction_id' => null]],
        ];
    }

    #[DataProvider('mismatchedPayments')]
    public function test_mismatched_provider_payment_is_not_activated(array $overrides): void
    {
        $this->configureGateway();
        $transaction = $this->order();
        Http::fake([$this->statusUrl($transaction) => Http::response($this->providerPayload($transaction, $overrides))]);

        $this->postJson(route('payments.midtrans.notification'), $this->signed($this->providerPayload($transaction)))->assertStatus(503);

        $this->assertSame(TransactionStatus::Pending, $transaction->fresh()->status);
        $this->assertDatabaseCount('user_packages', 0);
        Http::assertSentCount(1);
    }

    /** @return array<string, array{string, ?string, string, int}> */
    public static function paymentStatuses(): array
    {
        return [
            'pending' => ['pending', null, 'pending', 0],
            'challenged capture' => ['capture', 'challenge', 'pending', 0],
            'capture without fraud decision' => ['capture', null, 'pending', 0],
            'accepted capture' => ['capture', 'accept', 'paid', 1],
            'denied capture' => ['capture', 'deny', 'failed', 0],
            'deny' => ['deny', null, 'failed', 0],
            'cancel' => ['cancel', null, 'failed', 0],
            'expire' => ['expire', null, 'expired', 0],
            'authorize' => ['authorize', 'accept', 'pending', 0],
        ];
    }

    #[DataProvider('paymentStatuses')]
    public function test_provider_status_controls_access(string $providerStatus, ?string $fraud, string $expected, int $grants): void
    {
        $this->configureGateway();
        $transaction = $this->order();
        $payload = $this->providerPayload($transaction, ['transaction_status' => $providerStatus, 'fraud_status' => $fraud]);
        Http::fake([$this->statusUrl($transaction) => Http::response($payload)]);

        $this->postJson(route('payments.midtrans.notification'), $this->signed($payload))->assertOk();

        $this->assertSame($expected, $transaction->fresh()->status->value);
        $this->assertDatabaseCount('user_packages', $grants);
        Http::assertSentCount(1);
    }

    public function test_paid_access_does_not_regress_or_reset_quota_or_manual_revocation(): void
    {
        $this->freezeTime();
        $this->configureGateway();
        $transaction = $this->order();
        $paid = $this->providerPayload($transaction);
        Http::fake([$this->statusUrl($transaction) => Http::sequence()->push($paid)
            ->push($this->providerPayload($transaction, ['transaction_status' => 'pending']))->push($paid)]);
        $this->postJson(route('payments.midtrans.notification'), $this->signed($paid))->assertOk();
        $grant = UserPackage::sole();
        $expiry = $grant->expires_at->toDateTimeString();
        $grant->update(['tryouts_used' => 1, 'revoked_at' => now()]);
        $this->travel(1)->hours();

        $this->postJson(route('payments.midtrans.notification'), $this->signed($paid))->assertOk();
        $this->postJson(route('payments.midtrans.notification'), $this->signed($paid))->assertOk();

        $this->assertSame(TransactionStatus::Paid, $transaction->fresh()->status);
        $this->assertDatabaseCount('user_packages', 1);
        $this->assertSame(1, $grant->fresh()->tryouts_used);
        $this->assertNotNull($grant->fresh()->revoked_at);
        $this->assertSame($expiry, $grant->fresh()->expires_at->toDateTimeString());
        Http::assertSentCount(3);
    }

    public function test_full_refund_revokes_access_and_partial_refund_retains_access(): void
    {
        $this->configureGateway();
        $transaction = $this->order();
        $paid = $this->providerPayload($transaction);
        $partial = $this->providerPayload($transaction, ['transaction_status' => 'partial_refund']);
        $refund = $this->providerPayload($transaction, ['transaction_status' => 'refund']);
        Http::fake([$this->statusUrl($transaction) => Http::sequence()->push($paid)->push($partial)->push($refund)->push($paid)]);
        $this->postJson(route('payments.midtrans.notification'), $this->signed($paid))->assertOk();

        $this->postJson(route('payments.midtrans.notification'), $this->signed($partial))->assertOk();
        $this->assertSame(TransactionStatus::PartiallyRefunded, $transaction->fresh()->status);
        $this->assertTrue(UserPackage::sole()->isActive());
        $this->postJson(route('payments.midtrans.notification'), $this->signed($refund))->assertOk();
        $this->assertSame(TransactionStatus::Refunded, $transaction->fresh()->status);
        $this->assertFalse(UserPackage::sole()->isActive());
        $this->postJson(route('payments.midtrans.notification'), $this->signed($paid))->assertOk();
        $this->assertSame(TransactionStatus::Refunded, $transaction->fresh()->status);
        $this->assertDatabaseCount('user_packages', 1);
        Http::assertSentCount(4);
    }

    public function test_other_participant_cannot_read_pay_or_refresh_order(): void
    {
        $this->configureGateway();
        $transaction = $this->order();

        $this->actingAs(User::factory()->create())->get(route('transactions.show', $transaction))->assertNotFound();
        $this->post(route('transactions.pay', $transaction))->assertNotFound();
        $this->post(route('transactions.refresh', $transaction))->assertNotFound();
        $this->get(route('transactions.index'))->assertDontSee($transaction->order_id);

        Http::assertNothingSent();
    }

    public function test_browser_callback_cannot_claim_success_without_provider_confirmation(): void
    {
        $this->configureGateway();
        $transaction = $this->order();
        Http::fake([$this->statusUrl($transaction) => Http::response(['status_code' => '404'], 404)]);

        $this->actingAs($transaction->user)->get(route('transactions.show', [$transaction, 'order_id' => $transaction->order_id, 'transaction_status' => 'settlement', 'status_code' => '200']))->assertSee('Menunggu pembayaran');

        $this->assertDatabaseCount('user_packages', 0);
        Http::assertSentCount(1);
    }

    public function test_status_not_found_only_expires_order_after_deadline(): void
    {
        $this->freezeTime();
        $this->configureGateway();
        $transaction = $this->order();
        Http::fake([$this->statusUrl($transaction) => Http::response(['status_code' => '404'], 404)]);

        $this->actingAs($transaction->user)->post(route('transactions.refresh', $transaction))->assertSessionHasNoErrors();
        $this->assertSame(TransactionStatus::Pending, $transaction->fresh()->status);
        $this->travel(25)->hours();
        $this->post(route('transactions.refresh', $transaction))->assertSessionHasNoErrors();

        $this->assertSame(TransactionStatus::Expired, $transaction->fresh()->status);
        $this->assertDatabaseCount('user_packages', 0);
        Http::assertSentCount(2);
    }

    public function test_provider_timeout_keeps_state_and_webhook_requests_retry(): void
    {
        $this->configureGateway();
        $transaction = $this->order();
        Http::fake([$this->statusUrl($transaction) => Http::failedConnection()]);

        $this->postJson(route('payments.midtrans.notification'), $this->signed($this->providerPayload($transaction)))->assertStatus(503);

        $this->assertSame(TransactionStatus::Pending, $transaction->fresh()->status);
        $this->assertDatabaseCount('user_packages', 0);
    }

    public function test_pending_purchase_blocks_manual_grant_and_admin_sees_real_filtered_transactions(): void
    {
        $this->configureGateway();
        $transaction = $this->order();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.packages.members.store', $transaction->package), [
            'email' => $transaction->user->email, 'note' => 'Manual',
        ])->assertInvalid(['email' => 'Peserta memiliki pembayaran paket yang masih pending']);
        $this->get(route('admin.transactions.index', ['q' => $transaction->user->email, 'status' => 'pending']))->assertSee($transaction->order_id)->assertSee('Sandbox');
        $this->get(route('admin.transactions.index', ['status' => 'paid']))->assertDontSee($transaction->order_id);
        $this->actingAs($transaction->user)->get(route('admin.transactions.index'))->assertForbidden();

        $this->assertDatabaseCount('user_packages', 0);
        Http::assertNothingSent();
    }

    public function test_scheduled_sync_recovers_paid_order_without_webhook_and_expires_abandoned_order(): void
    {
        $this->freezeTime();
        $this->configureGateway();
        $paid = $this->order();
        $abandoned = $this->order();
        $abandoned->update(['expires_at' => now()->subSecond()]);
        Http::fake([
            $this->statusUrl($paid) => Http::response($this->providerPayload($paid)),
            $this->statusUrl($abandoned) => Http::response(['status_code' => '404'], 404),
        ]);

        $this->artisan('payments:sync-midtrans')->assertExitCode(0);

        $this->assertSame(TransactionStatus::Paid, $paid->fresh()->status);
        $this->assertSame(TransactionStatus::Expired, $abandoned->fresh()->status);
        $this->assertDatabaseCount('user_packages', 1);
        Http::assertSentCount(2);
    }

    public function test_unavailable_selected_content_blocks_purchase_even_when_other_content_remains(): void
    {
        $this->configureGateway();
        $package = $this->package();
        $archived = Material::factory()->premium()->video()->create(['title' => 'Konten yang diarsipkan']);
        $package->materials()->attach($archived);
        $archived->delete();

        $this->actingAs(User::factory()->create())->get(route('checkout', ['package' => $package->id]))
            ->assertDontSee('Konten yang diarsipkan')->assertSee('Paket ini belum bisa dibeli')->assertDontSee('Lanjut ke pembayaran');
        $this->get(route('packages.index'))->assertSee('Konten belum tersedia')
            ->assertDontSee(route('checkout', ['package' => $package->id]), false);
        $this->post(route('checkout.store'), ['package_id' => $package->id])->assertInvalid(['package_id']);

        $this->assertDatabaseCount('transactions', 0);
        Http::assertNothingSent();
    }

    /** @return array<string, array{string, bool}> */
    public static function unavailableExamStates(): array
    {
        return ['archived exam' => ['published', true], 'draft exam' => ['draft', false]];
    }

    #[DataProvider('unavailableExamStates')]
    public function test_checkout_and_catalogue_disable_purchase_when_a_selected_exam_is_unavailable(string $status, bool $archived): void
    {
        $this->configureGateway();
        $package = Package::factory()->create();
        $exam = Exam::factory()->create(['status' => $status, 'deleted_at' => $archived ? now() : null]);
        $package->exams()->attach($exam);
        $this->actingAs(User::factory()->create());

        $this->get(route('checkout', ['package' => $package->id]))
            ->assertSee('Paket ini belum bisa dibeli')->assertDontSee('Lanjut ke pembayaran');
        $this->get(route('packages.index'))->assertSee('Konten belum tersedia')
            ->assertDontSee(route('checkout', ['package' => $package->id]), false);
        $this->post(route('checkout.store'), ['package_id' => $package->id])->assertInvalid('package_id');

        $this->assertDatabaseCount('transactions', 0);
        Http::assertNothingSent();
    }

    public function test_admin_can_replace_archived_package_content_to_restore_checkout(): void
    {
        $this->configureGateway();
        Http::fake([self::SNAP => Http::response(['redirect_url' => self::PAYMENT_URL], 201)]);
        $package = Package::factory()->create();
        $archived = Exam::factory()->create(['deleted_at' => now()]);
        $replacement = Exam::factory()->create();
        $package->exams()->attach($archived);
        $this->actingAs(User::factory()->admin()->create());
        $this->get(route('admin.packages.index'))->assertSee('Konten belum tersedia. Perbarui isi paket agar dapat dibeli.');
        $this->get(route('admin.packages.edit', $package))->assertSee('Konten paket belum tersedia');

        $this->put(route('admin.packages.update', $package), [
            'name' => $package->name, 'description' => 'Paket tryout pengganti', 'price' => $package->price,
            'duration_days' => $package->duration_days, 'tryout_quota' => 2, 'position' => 0,
            'is_active' => true, 'material_ids' => [], 'exam_ids' => [$replacement->id],
        ])->assertSessionHasNoErrors();
        $this->get(route('admin.packages.edit', $package))->assertDontSee('Konten paket belum tersedia');
        $this->actingAs(User::factory()->create())->get(route('checkout', ['package' => $package->id]))
            ->assertSee('Lanjut ke pembayaran')->assertSee($replacement->title);
        $this->post(route('checkout.store'), ['package_id' => $package->id])->assertSessionHasNoErrors();

        $this->assertSame([$replacement->id], array_column(Transaction::sole()->package_snapshot['exams'], 'id'));
        $this->assertDatabaseHas('exam_package', ['package_id' => $package->id, 'exam_id' => $replacement->id]);
        $this->assertDatabaseMissing('exam_package', ['package_id' => $package->id, 'exam_id' => $archived->id]);
        Http::assertSentCount(1);
    }

    public function test_notification_for_unknown_order_is_rejected(): void
    {
        $this->configureGateway();
        $payload = ['order_id' => 'unknown-order', 'status_code' => '200', 'gross_amount' => '75000.00'];

        $this->postJson(route('payments.midtrans.notification'), $this->signed($payload))->assertNotFound();

        $this->assertDatabaseCount('user_packages', 0);
        Http::assertNothingSent();
    }

    public function test_temporarily_unavailable_provider_status_requests_webhook_retry(): void
    {
        $this->configureGateway();
        $transaction = $this->order();
        Http::fake([$this->statusUrl($transaction) => Http::response(['status_code' => '404'], 404)]);

        $this->postJson(route('payments.midtrans.notification'), $this->signed($this->providerPayload($transaction)))->assertStatus(503);

        $this->assertDatabaseCount('user_packages', 0);
        Http::assertSentCount(1);
    }

    public function test_new_provider_attempt_for_same_pending_order_can_become_paid(): void
    {
        $this->configureGateway();
        $transaction = $this->order();
        $transaction->update(['provider_reference' => 'earlier-payment-attempt', 'provider_status' => 'pending']);
        $payload = $this->providerPayload($transaction);
        Http::fake([$this->statusUrl($transaction) => Http::response($payload)]);

        $this->postJson(route('payments.midtrans.notification'), $this->signed($payload))->assertOk();

        $this->assertSame(TransactionStatus::Paid, $transaction->fresh()->status);
        $this->assertSame($payload['transaction_id'], $transaction->fresh()->provider_reference);
        $this->assertDatabaseCount('user_packages', 1);
        Http::assertSentCount(1);
    }

    public function test_production_uses_production_endpoint_and_snap_url(): void
    {
        $this->configureGateway();
        config(['services.midtrans.is_production' => true]);
        $endpoint = 'https://app.midtrans.com/snap/v1/transactions';
        $paymentUrl = 'https://app.midtrans.com/snap/v3/redirection/test-token';
        Http::fake([$endpoint => Http::response(['redirect_url' => $paymentUrl], 201)]);
        $user = User::factory()->create();
        $package = $this->package();

        $this->actingAs($user)->post(route('checkout.store'), ['package_id' => $package->id])->assertSessionHasNoErrors();
        $transaction = Transaction::sole();
        $this->post(route('transactions.pay', $transaction))->assertRedirect($paymentUrl);

        $this->assertTrue($transaction->is_production);
        Http::assertSent(fn (Request $request): bool => $request->url() === $endpoint);
        Http::assertSentCount(1);
    }

    public function test_provider_redirect_cannot_send_participant_to_another_host(): void
    {
        $this->configureGateway();
        Http::fake([self::SNAP => Http::response(['redirect_url' => 'https://example.test/fake-payment'], 201)]);
        $user = User::factory()->create();
        $package = $this->package();

        $this->actingAs($user)->post(route('checkout.store'), ['package_id' => $package->id])->assertSessionHasErrors('payment');

        $this->assertNull(Transaction::sole()->payment_url);
        $this->assertDatabaseCount('user_packages', 0);
        Http::assertSentCount(1);
    }

    public function test_scheduled_sync_handles_failed_check_without_losing_pending_order(): void
    {
        $this->freezeTime();
        $this->configureGateway();
        $transaction = $this->order();
        Http::fake([$this->statusUrl($transaction) => Http::failedConnection()]);

        $this->artisan('payments:sync-midtrans')->assertExitCode(1);

        $this->assertSame(TransactionStatus::Pending, $transaction->fresh()->status);
        $this->assertNotNull($transaction->fresh()->checked_at);
        $this->assertDatabaseCount('user_packages', 0);
    }
}
