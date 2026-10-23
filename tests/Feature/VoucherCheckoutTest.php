<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TransactionStatus;
use App\Models\Material;
use App\Models\Package;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VoucherCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private const SNAP = 'https://app.sandbox.midtrans.com/snap/v1/transactions';

    private const PAYMENT_URL = 'https://app.sandbox.midtrans.com/snap/v3/redirection/voucher-test';

    private function configureGateway(): void
    {
        config(['services.midtrans.server_key' => 'voucher-test-key', 'services.midtrans.is_production' => false]);
        Http::preventStrayRequests();
        Http::fake([self::SNAP => Http::response(['redirect_url' => self::PAYMENT_URL], 201)]);
    }

    private function package(int $price = 150000): Package
    {
        $package = Package::factory()->create(['price' => $price]);
        $package->materials()->attach(Material::factory()->premium()->video()->create());

        return $package;
    }

    public function test_preview_and_checkout_apply_server_discount_and_ignore_forged_totals(): void
    {
        $this->freezeTime();
        $this->configureGateway();
        $package = $this->package();
        $voucher = Voucher::factory()->create(['code' => 'HEMAT20', 'minimum_amount' => 150000,
            'starts_on' => today(), 'ends_on' => today(), 'package_id' => $package->id]);
        $this->actingAs(User::factory()->create());

        $this->get(route('checkout', ['package' => $package->id, 'voucher_code' => ' hemat20 ']))
            ->assertSee('Voucher HEMAT20 diterapkan')->assertSee('Rp130.000');
        $this->assertDatabaseCount('transactions', 0);
        $this->post(route('checkout.store'), ['package_id' => $package->id, 'voucher_code' => ' hemat20 ',
            'amount' => 1, 'discount_amount' => 149999, 'voucher_id' => 999999, 'status' => 'paid'])->assertSessionHasNoErrors();
        $transaction = Transaction::sole();

        $this->assertSame(130000, $transaction->amount);
        $this->assertSame(20000, $transaction->discount_amount);
        $this->assertSame($voucher->id, $transaction->voucher_id);
        $this->assertSame('HEMAT20', $transaction->voucher_code);
        $this->assertSame(TransactionStatus::Pending, $transaction->status);
        $this->get(route('transactions.show', $transaction))->assertSee('Voucher HEMAT20')->assertSee('Rp130.000')->assertSee('Rp150.000');
        Http::assertSent(fn (Request $request): bool => $request->url() === self::SNAP
            && $request['transaction_details']['gross_amount'] === 130000 && $request['item_details'][0]['price'] === 130000);
    }

    public function test_percentage_discounts_are_rounded_down_to_whole_rupiah(): void
    {
        $this->configureGateway();
        $package = $this->package(99999);
        $voucher = Voucher::factory()->percentage(15)->create();

        $this->actingAs(User::factory()->create())->post(route('checkout.store'),
            ['package_id' => $package->id, 'voucher_code' => $voucher->code])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('transactions', ['voucher_id' => $voucher->id, 'discount_amount' => 14999, 'amount' => 85000]);
        Http::assertSentCount(1);
    }

    /** @return array<string, array{string, int}> */
    public static function fullDiscounts(): array
    {
        return ['100 percent' => ['percentage', 100], 'nominal larger than price' => ['fixed', 200000]];
    }

    #[DataProvider('fullDiscounts')]
    public function test_full_discount_activates_without_midtrans(string $type, int $value): void
    {
        $this->freezeTime();
        config(['services.midtrans.server_key' => '']);
        Http::preventStrayRequests();
        $package = $this->package();
        $voucher = Voucher::factory()->create(['type' => $type, 'value' => $value]);
        $this->actingAs(User::factory()->create());

        $this->get(route('checkout', ['package' => $package->id, 'voucher_code' => $voucher->code]))
            ->assertSee('Aktifkan paket gratis');
        $this->post(route('checkout.store'), ['package_id' => $package->id, 'voucher_code' => $voucher->code])->assertSessionHasNoErrors();

        $transaction = Transaction::sole();
        $this->assertSame(0, $transaction->amount);
        $this->assertSame(150000, $transaction->discount_amount);
        $this->assertSame(TransactionStatus::Paid, $transaction->status);
        $this->assertSame('free', $transaction->provider);
        $this->assertDatabaseHas('user_packages', ['id' => $transaction->user_package_id, 'price' => 0]);
        Http::assertNothingSent();
    }

    /** @return array<string, array{array<string, mixed>, string}> */
    public static function unavailableVouchers(): array
    {
        return ['inactive' => [['is_active' => false], 'Voucher sedang nonaktif.'],
            'future' => [['starts_on' => '2026-10-22'], 'Voucher belum mulai berlaku.'],
            'expired' => [['ends_on' => '2026-10-20'], 'Voucher sudah kedaluwarsa.'],
            'minimum' => [['minimum_amount' => 150001], 'Harga paket belum memenuhi minimum transaksi voucher.'],
            'another package' => [['package_id' => 'other'], 'Voucher tidak berlaku untuk paket ini.']];
    }

    #[DataProvider('unavailableVouchers')]
    public function test_invalid_voucher_cannot_create_order(array $attributes, string $message): void
    {
        $this->travelTo('2026-10-21 23:59:00');
        $this->configureGateway();
        $package = $this->package();
        if (($attributes['package_id'] ?? null) === 'other') {
            $attributes['package_id'] = Package::factory()->create()->id;
        }
        $voucher = Voucher::factory()->create($attributes);
        $this->actingAs(User::factory()->create());

        $this->get(route('checkout', ['package' => $package->id, 'voucher_code' => $voucher->code]))
            ->assertSee($message)->assertDontSee('Lanjut ke pembayaran');
        $this->post(route('checkout.store'), ['package_id' => $package->id, 'voucher_code' => $voucher->code])
            ->assertInvalid(['voucher_code' => $message]);

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('user_packages', 0);
        Http::assertNothingSent();
    }

    public function test_missing_unknown_and_malformed_voucher_codes_are_handled(): void
    {
        $this->configureGateway();
        $package = $this->package();
        $this->actingAs(User::factory()->create());

        $this->post(route('checkout.store'), ['package_id' => $package->id, 'voucher_code' => 'MISSING'])
            ->assertInvalid(['voucher_code' => 'Kode voucher tidak ditemukan.']);
        $this->post(route('checkout.store'), ['package_id' => $package->id, 'voucher_code' => ['bad']])->assertInvalid(['voucher_code']);
        $this->post(route('checkout.store'), ['package_id' => $package->id, 'voucher_code' => str_repeat('A', 41)])->assertInvalid(['voucher_code']);
        $this->assertDatabaseCount('transactions', 0);
        $this->post(route('checkout.store'), ['package_id' => $package->id, 'voucher_code' => ''])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('transactions', ['amount' => 150000, 'discount_amount' => 0, 'voucher_id' => null]);
        Http::assertSentCount(1);
    }

    public function test_free_package_does_not_consume_a_voucher(): void
    {
        $this->configureGateway();
        $package = $this->package(0);
        $voucher = Voucher::factory()->create();

        $this->actingAs(User::factory()->create())->post(route('checkout.store'),
            ['package_id' => $package->id, 'voucher_code' => $voucher->code])
            ->assertInvalid(['voucher_code' => 'Voucher tidak dapat digunakan pada paket gratis.']);

        $this->assertDatabaseCount('transactions', 0);
        Http::assertNothingSent();
    }

    public function test_pending_order_reserves_last_slot_and_retries_do_not_consume_another(): void
    {
        $this->configureGateway();
        $package = $this->package();
        $voucher = Voucher::factory()->create(['usage_limit' => 1]);
        $first = User::factory()->create();
        $payload = ['package_id' => $package->id, 'voucher_code' => $voucher->code];
        $this->actingAs($first)->post(route('checkout.store'), $payload)->assertSessionHasNoErrors();
        $transaction = Transaction::sole();

        $this->post(route('checkout.store'), $payload)->assertRedirect(route('transactions.show', $transaction));
        $this->actingAs(User::factory()->create())->post(route('checkout.store'), $payload)
            ->assertInvalid(['voucher_code' => 'Kuota voucher sudah habis.']);

        $this->assertDatabaseCount('transactions', 1);
        $this->assertSame(130000, $transaction->fresh()->amount);
        Http::assertSentCount(1);
    }

    /** @return array<string, array{string, bool}> */
    public static function reservationStatuses(): array
    {
        return ['pending' => ['pending', false], 'paid' => ['paid', false], 'refund' => ['refunded', false],
            'partial refund' => ['partially_refunded', false], 'failed' => ['failed', true], 'expired' => ['expired', true]];
    }

    #[DataProvider('reservationStatuses')]
    public function test_only_failed_or_confirmed_expired_orders_release_quota(string $status, bool $available): void
    {
        $this->freezeTime();
        $this->configureGateway();
        $package = $this->package();
        $voucher = Voucher::factory()->create(['usage_limit' => 1]);
        Transaction::factory()->for($voucher)->for($package)->create(['status' => $status, 'expires_at' => now()->subHour()]);
        $this->actingAs(User::factory()->create());

        $response = $this->post(route('checkout.store'), ['package_id' => $package->id, 'voucher_code' => $voucher->code]);

        if ($available) {
            $response->assertSessionHasNoErrors();
            $this->assertDatabaseCount('transactions', 2);
            Http::assertSentCount(1);
        } else {
            $response->assertInvalid(['voucher_code' => 'Kuota voucher sudah habis.']);
            $this->assertDatabaseCount('transactions', 1);
            Http::assertNothingSent();
        }
    }

    public function test_voucher_is_rechecked_after_preview_and_unconfigured_gateway_does_not_reserve_quota(): void
    {
        $this->configureGateway();
        $package = $this->package();
        $voucher = Voucher::factory()->create(['usage_limit' => 1]);
        $this->actingAs(User::factory()->create());
        $this->get(route('checkout', ['package' => $package->id, 'voucher_code' => $voucher->code]))->assertSee('diterapkan');
        $voucher->update(['is_active' => false]);

        $this->post(route('checkout.store'), ['package_id' => $package->id, 'voucher_code' => $voucher->code])
            ->assertInvalid(['voucher_code' => 'Voucher sedang nonaktif.']);
        $voucher->update(['is_active' => true]);
        config(['services.midtrans.server_key' => '']);
        $this->post(route('checkout.store'), ['package_id' => $package->id, 'voucher_code' => $voucher->code])->assertInvalid(['package_id']);

        $this->assertDatabaseCount('transactions', 0);
        Http::assertNothingSent();
    }

    public function test_existing_order_keeps_original_voucher_and_rejects_switching_codes(): void
    {
        $this->configureGateway();
        $package = $this->package();
        $voucher = Voucher::factory()->create(['code' => 'ORIGINAL']);
        $other = Voucher::factory()->create(['code' => 'OTHER']);
        $this->actingAs(User::factory()->create())->post(route('checkout.store'),
            ['package_id' => $package->id, 'voucher_code' => $voucher->code])->assertSessionHasNoErrors();
        $transaction = Transaction::sole();
        $voucher->update(['code' => 'RENAMED', 'value' => 99999, 'is_active' => false]);
        $package->update(['price' => 999999]);

        $this->get(route('checkout', ['package' => $package->id, 'voucher_code' => 'ORIGINAL']))->assertRedirect(route('transactions.show', $transaction));
        $this->post(route('checkout.store'), ['package_id' => $package->id, 'voucher_code' => 'ORIGINAL'])->assertRedirect(route('transactions.show', $transaction));
        $this->post(route('checkout.store'), ['package_id' => $package->id, 'voucher_code' => $other->code])->assertInvalid(['voucher_code']);
        $this->post(route('checkout.store'), ['package_id' => $package->id])->assertRedirect(route('transactions.show', $transaction));

        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'voucher_code' => 'ORIGINAL', 'amount' => 130000, 'discount_amount' => 20000]);
        $this->assertDatabaseCount('transactions', 1);
        Http::assertSentCount(1);
    }

    public function test_discounted_payment_settles_once_using_saved_amount_after_voucher_changes(): void
    {
        $this->configureGateway();
        $package = $this->package();
        $voucher = Voucher::factory()->create();
        $this->actingAs(User::factory()->create())->post(route('checkout.store'),
            ['package_id' => $package->id, 'voucher_code' => $voucher->code])->assertSessionHasNoErrors();
        $transaction = Transaction::sole();
        $voucher->update(['value' => 50000, 'is_active' => false]);
        $statusUrl = 'https://api.sandbox.midtrans.com/v2/'.$transaction->order_id.'/status';
        Http::fake([$statusUrl => Http::response(['order_id' => $transaction->order_id, 'gross_amount' => '130000.00',
            'transaction_id' => 'voucher-payment', 'transaction_status' => 'settlement'])]);

        $this->post(route('transactions.refresh', $transaction))->assertSessionHasNoErrors();
        $this->post(route('transactions.refresh', $transaction))->assertSessionHasNoErrors();

        $this->assertSame(TransactionStatus::Paid, $transaction->fresh()->status);
        $this->assertDatabaseCount('user_packages', 1);
        $this->assertDatabaseHas('user_packages', ['id' => $transaction->fresh()->user_package_id, 'price' => 130000]);
        Http::assertSentCount(2);
    }
}
