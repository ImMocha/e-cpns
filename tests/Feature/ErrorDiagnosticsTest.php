<?php

namespace Tests\Feature;

use App\Models\IntegrationSetting;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class ErrorDiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_setting_requires_admin_password_and_rejects_stale_versions(): void
    {
        $data = ['enabled' => 1, 'version' => 0, 'current_password' => 'password'];
        $url = route('admin.settings.error-diagnostics.update');
        $this->patch($url, $data)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->patch($url, $data)->assertForbidden();
        $this->actingAs(User::factory()->admin()->create());
        $this->patch($url, array_replace($data, ['current_password' => 'wrong']))->assertSessionHasErrors('current_password');
        $this->patch($url, $data)->assertSessionHasNoErrors()->assertRedirect(route('admin.settings.index'));
        $this->assertTrue(IntegrationSetting::findOrFail('error_diagnostics')->settings['enabled']);
        $this->assertDatabaseHas('integration_setting_audits', ['key' => 'error_diagnostics', 'action' => 'updated']);
        $this->patch($url, $data)->assertSessionHasErrors('version');
        $this->patch($url, array_replace($data, ['enabled' => 0, 'version' => 1]))->assertSessionHasNoErrors();
        $this->assertFalse(IntegrationSetting::findOrFail('error_diagnostics')->settings['enabled']);
    }

    public function test_diagnostics_are_opt_in_admin_only_and_keep_secrets_out_of_html_and_json(): void
    {
        config(['app.debug' => false]);
        Route::get('/_tests/diagnostic', static fn () => throw new RuntimeException('password=private-secret'));
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get('/_tests/diagnostic')->assertStatus(500)->assertDontSee('Diagnostik admin');
        IntegrationSetting::create(['key' => 'error_diagnostics', 'settings' => ['enabled' => true]]);
        $this->get('/_tests/diagnostic')->assertStatus(500)->assertSee('Diagnostik admin')->assertSee('RuntimeException')
            ->assertSee('Kode referensi')->assertHeader('Cache-Control', 'no-store, private')->assertDontSee('private-secret');
        $this->getJson('/_tests/diagnostic')->assertStatus(500)->assertJsonPath('message', 'Server Error')->assertDontSee('Diagnostik admin');
        $this->actingAs(User::factory()->create())->get('/_tests/diagnostic')->assertStatus(500)->assertDontSee('Diagnostik admin')->assertDontSee('private-secret');
    }

    public function test_admin_receives_safe_route_reason_and_http_error_explanations(): void
    {
        config(['app.debug' => false]);
        IntegrationSetting::create(['key' => 'error_diagnostics', 'settings' => ['enabled' => true]]);
        $this->actingAs(User::factory()->admin()->create());
        Route::get('/_tests/missing-route', static fn () => route('missing.example'));
        $this->get('/_tests/missing-route')->assertStatus(500)->assertSee('Route missing.example belum terdaftar');
        $this->get('/_tests/missing-page')->assertNotFound()->assertSee('Alamat halaman atau data yang diminta tidak ditemukan.');

    }

    public function test_database_errors_show_sqlstate_without_query_or_bindings(): void
    {
        config(['app.debug' => false]);
        IntegrationSetting::create(['key' => 'error_diagnostics', 'settings' => ['enabled' => true]]);
        $this->actingAs(User::factory()->admin()->create());
        Route::get('/_tests/database-error', static fn () => throw new QueryException(
            'sqlite', 'select private_secret from missing_table where password = ?', ['private-password'], new \PDOException('Database failure', 42),
        ));
        $this->get('/_tests/database-error')->assertStatus(500)->assertSee('SQLSTATE 42')
            ->assertDontSee('private_secret')->assertDontSee('private-password');
    }
}
