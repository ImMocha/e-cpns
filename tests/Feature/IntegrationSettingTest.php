<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TransactionStatus;
use App\IntegrationSettings;
use App\MidtransGateway;
use App\Models\IntegrationSetting;
use App\Models\Material;
use App\Models\Package;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class IntegrationSettingTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function paymentData(array $overrides = []): array
    {
        return array_replace([
            'version' => 0, 'enabled' => '1', 'is_production' => '0',
            'sandbox_server_key' => 'SB-Mid-server-private', 'production_server_key' => 'Mid-server-private',
            'current_password' => 'password',
        ], $overrides);
    }

    /** @return array<string, mixed> */
    private function mailData(array $overrides = []): array
    {
        return array_replace([
            'version' => 0, 'enabled' => '1', 'smtp_host' => 'smtp.gmail.com', 'smtp_port' => '465',
            'smtp_username' => 'sender@example.com', 'smtp_password' => 'private-app-password',
            'from_address' => 'sender@example.com', 'from_name' => 'Ganesha Nusa', 'current_password' => 'password',
        ], $overrides);
    }

    /** @return array<string, mixed> */
    private function providerPayload(Transaction $transaction, string $key): array
    {
        $payload = [
            'order_id' => $transaction->order_id, 'gross_amount' => $transaction->amount.'.00',
            'status_code' => '200', 'transaction_id' => 'provider-'.$transaction->id,
            'transaction_status' => 'settlement', 'currency' => 'IDR',
        ];
        $payload['signature_key'] = hash('sha512', $payload['order_id'].'200'.$payload['gross_amount'].$key);

        return $payload;
    }

    public function test_settings_require_an_active_verified_admin(): void
    {
        $this->get(route('admin.settings.index'))->assertRedirect(route('login'));
        $this->patch(route('admin.settings.update', 'midtrans'), $this->paymentData())->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('admin.settings.index'))->assertForbidden();
        $this->patch(route('admin.settings.update', 'midtrans'), $this->paymentData())->assertForbidden();
        $this->post(route('admin.settings.test', 'mail'), ['current_password' => 'password'])->assertForbidden();
        $this->actingAs(User::factory()->admin()->unverified()->create())->get(route('admin.settings.index'))->assertRedirect(route('verification.notice'));
        $this->actingAs(User::factory()->admin()->inactive()->create())->get(route('admin.settings.index'))->assertRedirect(route('login'));

        $this->assertDatabaseCount('integration_setting_audits', 0);
        $this->assertDatabaseHas('integration_settings', ['key' => 'midtrans', 'version' => 0, 'settings' => null]);
    }

    public function test_secrets_are_encrypted_hidden_and_absent_from_the_page_and_audit(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->patch(route('admin.settings.update', 'midtrans'), $this->paymentData())
            ->assertRedirect(route('admin.settings.index'))->assertSessionHasNoErrors();
        $this->patch(route('admin.settings.update', 'mail'), $this->mailData())->assertSessionHasNoErrors();
        $this->get(route('admin.settings.index'))->assertSee('Pengaturan Integrasi')
            ->assertDontSee('SB-Mid-server-private')->assertDontSee('Mid-server-private')->assertDontSee('private-app-password')
            ->assertHeader('Cache-Control', 'no-store, private');

        $ciphertext = DB::table('integration_settings')->where('key', 'midtrans')->value('settings');
        $this->assertStringNotContainsString('SB-Mid-server-private', $ciphertext);
        $this->assertStringNotContainsString('private-app-password', DB::table('integration_settings')->where('key', 'mail')->value('settings'));
        $record = IntegrationSetting::query()->findOrFail('midtrans');
        $this->assertSame('SB-Mid-server-private', $record->settings['sandbox_server_key']);
        $this->assertArrayNotHasKey('settings', $record->toArray());
        $audit = DB::table('integration_setting_audits')->where('key', 'midtrans')->sole();
        $this->assertSame($admin->id, $audit->actor_id);
        $this->assertStringContainsString('sandbox_server_key', $audit->changed_fields);
        $this->assertStringNotContainsString('SB-Mid-server-private', json_encode($audit));
    }

    public function test_invalid_password_rejects_changes_and_does_not_flash_secrets(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->from(route('admin.settings.index'))
            ->patch(route('admin.settings.update', 'midtrans'), $this->paymentData(['current_password' => 'wrong']))
            ->assertSessionHasErrors(['current_password' => 'Password akun admin tidak cocok.']);

        $this->assertNull(session()->getOldInput('sandbox_server_key'));
        $this->assertNull(session()->getOldInput('production_server_key'));
        $this->assertNull(session()->getOldInput('current_password'));
        $this->assertDatabaseHas('integration_settings', ['key' => 'midtrans', 'version' => 0, 'settings' => null]);
        $this->assertDatabaseCount('integration_setting_audits', 0);
    }

    public function test_blank_secret_inputs_preserve_credentials_and_unknown_fields_are_ignored(): void
    {
        $admin = User::factory()->admin()->create();
        $settings = app(IntegrationSettings::class);
        $settings->update('midtrans', $this->paymentData(), $admin, null);

        $this->actingAs($admin)->patch(route('admin.settings.update', 'midtrans'), $this->paymentData([
            'version' => 1, 'sandbox_server_key' => '', 'production_server_key' => '',
            'enabled' => '0', 'APP_KEY' => 'attacker-value', 'updated_by' => 999,
        ]))->assertSessionHasNoErrors();

        $record = IntegrationSetting::query()->findOrFail('midtrans');
        $this->assertSame('SB-Mid-server-private', $record->settings['sandbox_server_key']);
        $this->assertSame('Mid-server-private', $record->settings['production_server_key']);
        $this->assertFalse($record->settings['enabled']);
        $this->assertArrayNotHasKey('APP_KEY', $record->settings);
        $this->assertSame($admin->id, $record->updated_by);
    }

    public function test_stale_page_cannot_overwrite_another_admins_change(): void
    {
        $admin = User::factory()->admin()->create();
        app(IntegrationSettings::class)->update('midtrans', $this->paymentData(), $admin, null);

        $this->actingAs($admin)->patch(route('admin.settings.update', 'midtrans'), $this->paymentData(['sandbox_server_key' => 'overwritten']))
            ->assertSessionHasErrors(['version' => 'Pengaturan sudah diubah admin lain. Muat ulang halaman sebelum menyimpan.']);

        $this->assertSame('SB-Mid-server-private', app(IntegrationSettings::class)->midtrans()['sandbox_server_key']);
        $this->assertDatabaseCount('integration_setting_audits', 1);
    }

    public function test_pending_transactions_block_key_replacement_and_removal(): void
    {
        config(['services.midtrans.server_key' => 'existing-key', 'services.midtrans.is_production' => false]);
        Transaction::factory()->create();
        $this->actingAs(User::factory()->admin()->create());

        $this->patch(route('admin.settings.update', 'midtrans'), $this->paymentData())->assertSessionHasErrors('sandbox_server_key');
        $this->patch(route('admin.settings.update', 'midtrans'), $this->paymentData([
            'enabled' => '0', 'sandbox_server_key' => '', 'remove_sandbox_server_key' => '1',
        ]))->assertSessionHasErrors('sandbox_server_key');

        $this->assertSame('existing-key', app(IntegrationSettings::class)->midtrans()['sandbox_server_key']);
        $this->assertDatabaseCount('integration_setting_audits', 0);
    }

    public function test_pausing_checkout_and_switching_mode_preserves_old_webhooks_and_both_modes_sync(): void
    {
        $this->freezeTime();
        config(['services.midtrans.server_key' => 'sandbox-old', 'services.midtrans.is_production' => false]);
        $sandbox = Transaction::factory()->create();
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->patch(route('admin.settings.update', 'midtrans'), $this->paymentData([
            'enabled' => '0', 'is_production' => '1', 'sandbox_server_key' => '', 'production_server_key' => 'production-new',
        ]))->assertSessionHasNoErrors();
        $production = Transaction::factory()->create(['is_production' => true]);
        $sandboxPayload = $this->providerPayload($sandbox, 'sandbox-old');
        $productionPayload = $this->providerPayload($production, 'production-new');
        Http::preventStrayRequests();
        Http::fake([
            'https://api.sandbox.midtrans.com/v2/'.$sandbox->order_id.'/status' => Http::response($sandboxPayload),
            'https://api.midtrans.com/v2/'.$production->order_id.'/status' => Http::response($productionPayload),
        ]);

        $this->postJson(route('payments.midtrans.notification'), $this->providerPayload($sandbox, 'production-new'))->assertForbidden();
        $this->postJson(route('payments.midtrans.notification'), $sandboxPayload)->assertOk();
        $this->artisan('payments:sync-midtrans')->assertExitCode(0);

        $this->assertSame(TransactionStatus::Paid, $sandbox->fresh()->status);
        $this->assertSame(TransactionStatus::Paid, $production->fresh()->status);
        $this->assertFalse(app(MidtransGateway::class)->configured());
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'api.sandbox.')
            && $request->hasHeader('Authorization', 'Basic '.base64_encode('sandbox-old:')));
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'api.midtrans.')
            && $request->hasHeader('Authorization', 'Basic '.base64_encode('production-new:')));
        Http::assertSentCount(2);
    }

    public function test_paused_payments_block_new_paid_checkout_but_allow_free_packages(): void
    {
        $admin = User::factory()->admin()->create();
        app(IntegrationSettings::class)->update('midtrans', $this->paymentData(['enabled' => false]), $admin, null);
        $package = Package::factory()->create();
        $package->materials()->attach(Material::factory()->video()->create());
        $this->actingAs(User::factory()->create());
        Http::preventStrayRequests();

        $this->post(route('checkout.store'), ['package_id' => $package->id])->assertSessionHasErrors('package_id');
        $this->assertDatabaseCount('transactions', 0);
        $package->update(['price' => 0]);
        $this->post(route('checkout.store'), ['package_id' => $package->id])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('transactions', ['provider' => 'free', 'status' => 'paid']);
        Http::assertNothingSent();
    }

    public function test_explicit_key_removal_does_not_reactivate_the_environment_fallback(): void
    {
        config(['services.midtrans.server_key' => 'environment-key', 'services.midtrans.is_production' => false]);

        $this->actingAs(User::factory()->admin()->create())->patch(route('admin.settings.update', 'midtrans'), $this->paymentData([
            'enabled' => '0', 'sandbox_server_key' => '', 'remove_sandbox_server_key' => '1',
        ]))->assertSessionHasNoErrors();

        $this->assertFalse(app(MidtransGateway::class)->configuredFor(false));
        $this->assertSame('', app(IntegrationSettings::class)->midtrans()['sandbox_server_key']);
    }

    public function test_enabled_payment_mode_requires_its_own_key(): void
    {
        config(['services.midtrans.server_key' => '']);

        $this->actingAs(User::factory()->admin()->create())->patch(route('admin.settings.update', 'midtrans'), $this->paymentData([
            'is_production' => '1', 'production_server_key' => '',
        ]))->assertSessionHasErrors(['production_server_key' => 'Isi server key untuk mode yang akan diaktifkan.']);

        $this->assertDatabaseCount('integration_setting_audits', 0);
    }

    /** @return array<string, array{array<string, mixed>, string}> */
    public static function unsafeSmtpSettings(): array
    {
        return [
            'loopback server' => [['smtp_host' => '127.0.0.1'], 'smtp_host'],
            'arbitrary server' => [['smtp_host' => 'internal.example.com'], 'smtp_host'],
            'unencrypted port' => [['smtp_port' => 25], 'smtp_port'],
            'header injection' => [['from_name' => "Admin\r\nBcc: attacker@example.com"], 'from_name'],
            'invalid email' => [['from_address' => 'invalid'], 'from_address'],
            'no app password' => [['smtp_password' => null], 'smtp_password'],
        ];
    }

    #[DataProvider('unsafeSmtpSettings')]
    public function test_unsafe_or_incomplete_smtp_settings_are_rejected(array $overrides, string $field): void
    {
        config(['mail.mailers.smtp.password' => null]);

        $this->actingAs(User::factory()->admin()->create())->patch(route('admin.settings.update', 'mail'), $this->mailData($overrides))
            ->assertSessionHasErrors($field);

        $this->assertDatabaseHas('integration_settings', ['key' => 'mail', 'version' => 0, 'settings' => null]);
        $this->assertNull(session()->getOldInput('smtp_password'));
        $this->assertDatabaseCount('integration_setting_audits', 0);
    }

    public function test_smtp_secret_is_preserved_then_can_be_explicitly_removed_while_disabled(): void
    {
        $admin = User::factory()->admin()->create();
        app(IntegrationSettings::class)->update('mail', $this->mailData(), $admin, null);
        $this->actingAs($admin)->patch(route('admin.settings.update', 'mail'), $this->mailData([
            'version' => 1, 'smtp_password' => '',
        ]))->assertSessionHasNoErrors();
        $this->assertSame('private-app-password', app(IntegrationSettings::class)->mail()['smtp_password']);

        $this->patch(route('admin.settings.update', 'mail'), $this->mailData([
            'version' => 2, 'enabled' => '0', 'smtp_password' => '', 'remove_smtp_password' => '1',
        ]))->assertSessionHasNoErrors();

        $this->assertSame('', app(IntegrationSettings::class)->mail()['smtp_password']);
        $this->assertFalse(app(IntegrationSettings::class)->mail()['enabled']);
    }

    public function test_midtrans_connection_test_authenticates_without_creating_a_charge(): void
    {
        $admin = User::factory()->admin()->create();
        app(IntegrationSettings::class)->update('midtrans', $this->paymentData(), $admin, null);
        Http::preventStrayRequests();
        Http::fake(['https://api.sandbox.midtrans.com/v2/integration-check-*/status' => Http::response(['status_code' => '404'], 404)]);

        $this->actingAs($admin)->post(route('admin.settings.test', 'midtrans'), [
            'current_password' => 'password', 'is_production' => '0',
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.settings.index'));

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->hasHeader('Authorization', 'Basic '.base64_encode('SB-Mid-server-private:')));
        Http::assertSentCount(1);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseHas('integration_setting_audits', ['action' => 'test_succeeded', 'key' => 'midtrans']);
    }

    public function test_connection_failure_does_not_expose_provider_response_or_credentials(): void
    {
        $admin = User::factory()->admin()->create();
        app(IntegrationSettings::class)->update('midtrans', $this->paymentData(), $admin, null);
        Http::preventStrayRequests();
        Http::fake(['https://api.sandbox.midtrans.com/v2/integration-check-*/status' => Http::response(['error' => 'SB-Mid-server-private secret response'], 401)]);

        $this->actingAs($admin)->from(route('admin.settings.index'))->post(route('admin.settings.test', 'midtrans'), [
            'current_password' => 'password', 'is_production' => '0',
        ])->assertSessionHasErrors('connection');
        $this->get(route('admin.settings.index'))->assertDontSee('secret response')->assertDontSee('SB-Mid-server-private');

        $this->assertDatabaseHas('integration_setting_audits', ['action' => 'test_failed', 'key' => 'midtrans']);
        Http::assertSentCount(1);
    }

    public function test_connection_tests_require_a_password_and_are_rate_limited(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Http::preventStrayRequests();

        $this->post(route('admin.settings.test', 'midtrans'), ['is_production' => '0'])->assertSessionHasErrors('current_password');
        $this->post(route('admin.settings.test', 'midtrans'), ['is_production' => '0'])->assertSessionHasErrors('current_password');
        $this->post(route('admin.settings.test', 'midtrans'), ['is_production' => '0'])->assertSessionHasErrors('current_password');
        $this->post(route('admin.settings.test', 'midtrans'), ['is_production' => '0'])->assertTooManyRequests();

        Http::assertNothingSent();
        $this->assertDatabaseCount('integration_setting_audits', 0);
    }

    public function test_email_test_only_sends_to_the_current_admin_using_saved_settings(): void
    {
        $admin = User::factory()->admin()->create();
        app(IntegrationSettings::class)->update('mail', $this->mailData(), $admin, null);
        $transport = new ArrayTransport;
        Mail::extend('smtp', fn (): ArrayTransport => $transport);

        $this->actingAs($admin)->post(route('admin.settings.test', 'mail'), [
            'current_password' => 'password', 'email' => 'attacker@example.com', 'smtp_password' => 'unsaved-password',
        ])->assertSessionHasNoErrors();

        $email = $transport->messages()->sole()->getOriginalMessage();
        $this->assertSame([$admin->email], array_map(fn ($address): string => $address->getAddress(), $email->getTo()));
        $this->assertSame('sender@example.com', $email->getFrom()[0]->getAddress());
        $this->assertSame('private-app-password', app(IntegrationSettings::class)->mail()['smtp_password']);
        $this->assertDatabaseHas('integration_setting_audits', ['key' => 'mail', 'action' => 'test_succeeded']);
    }

    public function test_production_settings_reject_http_connections(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.settings.index'))->assertForbidden();
    }
}
