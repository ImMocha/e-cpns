<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MaintenanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $storage = Storage::fake('maintenance-tests');
        $storage->makeDirectory('framework');
        $this->app->useStoragePath($storage->path(''));
    }

    public function test_admin_can_enable_and_disable_maintenance_without_losing_admin_access(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->patch(route('admin.settings.maintenance.update'), [
            'enabled' => '1', 'current_password' => 'password',
        ])->assertRedirect(route('admin.settings.index'))->assertSessionHas('success');

        $this->assertTrue($this->app->isDownForMaintenance());
        $this->get(route('admin.settings.index'))->assertOk()->assertSee('Maintenance aktif');
        $this->get('/')->assertServiceUnavailable()->assertHeader('Retry-After', '60')->assertSee('Kami sedang berbenah.');

        $this->patch(route('admin.settings.maintenance.update'), [
            'enabled' => '0', 'current_password' => 'password',
        ])->assertRedirect(route('admin.settings.index'))->assertSessionHas('success');

        $this->assertFalse($this->app->isDownForMaintenance());
        $this->get('/')->assertOk();
    }

    public function test_guests_and_members_cannot_manage_maintenance(): void
    {
        $this->get(route('admin.settings.index'))->assertRedirect(route('login'));
        $this->patch(route('admin.settings.maintenance.update'), ['enabled' => '1', 'current_password' => 'password'])
            ->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('admin.settings.index'))->assertForbidden();
        $this->patch(route('admin.settings.maintenance.update'), ['enabled' => '1', 'current_password' => 'password'])->assertForbidden();

        $this->assertFalse($this->app->isDownForMaintenance());
    }

    public function test_inactive_and_unverified_admins_cannot_change_maintenance(): void
    {
        $this->actingAs(User::factory()->admin()->inactive()->create())
            ->patch(route('admin.settings.maintenance.update'), ['enabled' => '1', 'current_password' => 'password'])
            ->assertRedirect(route('login'));
        $this->actingAs(User::factory()->admin()->unverified()->create())
            ->patch(route('admin.settings.maintenance.update'), ['enabled' => '1', 'current_password' => 'password'])
            ->assertRedirect(route('verification.notice'));

        $this->assertFalse($this->app->isDownForMaintenance());
    }

    #[DataProvider('invalidSettings')]
    public function test_invalid_settings_do_not_activate_maintenance(array $data, string $field, string $message): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->from(route('admin.settings.index'))
            ->patch(route('admin.settings.maintenance.update'), $data)
            ->assertRedirect(route('admin.settings.index'))->assertSessionHasErrors([$field => $message]);

        $this->assertFalse($this->app->isDownForMaintenance());
    }

    /** @return array<string, array{array<string, mixed>, string, string}> */
    public static function invalidSettings(): array
    {
        return [
            'incorrect password' => [['enabled' => '1', 'current_password' => 'wrong'], 'current_password', 'Password akun admin tidak cocok.'],
            'missing password' => [['enabled' => '1'], 'current_password', 'Masukkan password akun admin untuk mengubah status maintenance.'],
            'invalid status' => [['enabled' => 'yes', 'current_password' => 'password'], 'enabled', 'Status maintenance tidak valid.'],
            'missing status' => [['current_password' => 'password'], 'enabled', 'Pilih status maintenance.'],
        ];
    }

    public function test_maintenance_blocks_members_but_keeps_login_and_payment_notifications_available(): void
    {
        config(['services.midtrans.server_key' => 'sandbox-test-key', 'services.midtrans.is_production' => false]);
        Artisan::call('down', ['--render' => 'errors::503', '--retry' => 60, '--no-interaction' => true]);

        $this->get(route('login'))->assertOk();
        $this->get(route('admin.settings.index'))->assertRedirect(route('login'));
        $this->post(route('payments.midtrans.notification'), [])->assertForbidden()->assertJsonPath('message', 'Signature tidak valid.');
        $this->getJson('/')->assertServiceUnavailable()->assertJsonPath('message', 'Service Unavailable');
        $this->actingAs(User::factory()->create())->get(route('dashboard'))
            ->assertServiceUnavailable()->assertSee('Kami sedang berbenah.');
    }

    public function test_maintenance_is_part_of_integration_settings_without_a_separate_page_or_previews(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('admin.settings.index'))->assertOk()
            ->assertSee('Mode maintenance')->assertSee('Website online')
            ->assertSee(route('admin.settings.maintenance.update'), false)
            ->assertDontSee('Maintenance &amp; Halaman Error', false)->assertDontSee('Pratinjau halaman');
        $this->get('/admin/settings/maintenance')->assertMethodNotAllowed();
        $this->get('/admin/settings/maintenance/preview/404')->assertNotFound();
        $this->assertFalse($this->app->isDownForMaintenance());
    }

    public function test_failed_maintenance_command_displays_an_error_instead_of_success(): void
    {
        Artisan::shouldReceive('call')->once()->with('down', [
            '--render' => 'errors::503', '--retry' => 60, '--no-interaction' => true,
        ])->andReturn(1);

        $this->actingAs(User::factory()->admin()->create())->from(route('admin.settings.index'))
            ->patch(route('admin.settings.maintenance.update'), ['enabled' => '1', 'current_password' => 'password'])
            ->assertRedirect(route('admin.settings.index'))->assertSessionHas('error')->assertSessionMissing('success');
    }
}
