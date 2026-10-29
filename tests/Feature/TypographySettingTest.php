<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\IntegrationSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TypographySettingTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function fontData(array $overrides = []): array
    {
        return array_replace([
            'version' => 0, 'heading_font' => 'poppins', 'body_font' => 'inter',
            'landing' => '1', 'member' => '0', 'admin' => '0', 'current_password' => 'password',
        ], $overrides);
    }

    public function test_only_active_verified_admins_can_change_fonts(): void
    {
        $this->patch(route('admin.settings.typography.update'), $this->fontData())->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->patch(route('admin.settings.typography.update'), $this->fontData())->assertForbidden();
        $this->actingAs(User::factory()->admin()->unverified()->create())->patch(route('admin.settings.typography.update'), $this->fontData())->assertRedirect(route('verification.notice'));
        $this->actingAs(User::factory()->admin()->inactive()->create())->patch(route('admin.settings.typography.update'), $this->fontData())->assertRedirect(route('login'));

        $this->assertDatabaseMissing('integration_settings', ['key' => 'typography']);
        $this->assertDatabaseCount('integration_setting_audits', 0);
    }

    public function test_admin_can_save_fonts_and_audit_the_change(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->patch(route('admin.settings.typography.update'), $this->fontData(['member' => '1']))
            ->assertRedirect(route('admin.settings.index'))->assertSessionHasNoErrors();

        $record = IntegrationSetting::query()->findOrFail('typography');
        $this->assertSame(['heading_font' => 'poppins', 'body_font' => 'inter', 'landing' => true, 'member' => true, 'admin' => false], $record->settings);
        $this->assertDatabaseHas('integration_settings', ['key' => 'typography', 'version' => 1, 'updated_by' => $admin->id]);
        $this->assertDatabaseHas('integration_setting_audits', ['key' => 'typography', 'action' => 'updated', 'actor_id' => $admin->id]);
        $this->get(route('admin.settings.index'))->assertSee('Font &amp; Tipografi', false)->assertSee('24 pilihan font')
            ->assertSee('data-font-preview="landing"', false)->assertSee('data-font-preview="member"', false)->assertSee('data-font-preview="admin"', false)
            ->assertSee('value="poppins" data-family="Poppins" data-category="Sans serif" selected', false);
    }

    /** @return array<string, array{array<string, mixed>, array<string, string>}> */
    public static function invalidFonts(): array
    {
        return [
            'unapproved heading' => [['heading_font' => 'evil"; color: red'], ['heading_font' => 'Pilih font judul dari daftar yang tersedia.']],
            'unapproved body' => [['body_font' => 'unknown'], ['body_font' => 'Pilih font teks dari daftar yang tersedia.']],
            'incorrect password' => [['current_password' => 'incorrect'], ['current_password' => 'Password akun admin tidak cocok.']],
            'missing password' => [['current_password' => ''], ['current_password' => 'Masukkan password akun admin untuk mengonfirmasi perubahan font.']],
        ];
    }

    #[DataProvider('invalidFonts')]
    public function test_invalid_choices_and_passwords_do_not_change_fonts(array $overrides, array $errors): void
    {
        $this->actingAs(User::factory()->admin()->create())->patch(route('admin.settings.typography.update'), $this->fontData($overrides))
            ->assertSessionHasErrors($errors);

        $this->assertDatabaseMissing('integration_settings', ['key' => 'typography']);
        $this->assertDatabaseCount('integration_setting_audits', 0);
    }

    public function test_stale_form_does_not_overwrite_another_admin_change(): void
    {
        $this->actingAs(User::factory()->admin()->create())->patch(route('admin.settings.typography.update'), $this->fontData())->assertSessionHasNoErrors();

        $this->patch(route('admin.settings.typography.update'), $this->fontData(['heading_font' => 'lora']))
            ->assertSessionHasErrors(['version' => 'Pengaturan sudah diubah admin lain. Muat ulang halaman sebelum menyimpan.']);

        $this->assertSame('poppins', IntegrationSetting::query()->findOrFail('typography')->settings['heading_font']);
        $this->assertDatabaseCount('integration_setting_audits', 1);
    }

    /** @return array<string, array{bool, bool, bool}> */
    public static function scopes(): array
    {
        return [
            'landing only' => [true, false, false],
            'member only' => [false, true, false],
            'admin only' => [false, false, true],
            'all areas' => [true, true, true],
            'disabled everywhere' => [false, false, false],
        ];
    }

    #[DataProvider('scopes')]
    public function test_fonts_apply_only_to_selected_areas(bool $landing, bool $member, bool $admin): void
    {
        $this->actingAs(User::factory()->admin()->create())->patch(route('admin.settings.typography.update'), $this->fontData([
            'landing' => (int) $landing, 'member' => (int) $member, 'admin' => (int) $admin,
        ]))->assertSessionHasNoErrors();

        $landingPage = $this->get(route('home'));
        $memberPage = $this->actingAs(User::factory()->create())->get(route('dashboard'));
        $adminPage = $this->actingAs(User::factory()->admin()->create())->get(route('admin.settings.index'));
        foreach ([[$landingPage, $landing], [$memberPage, $member], [$adminPage, $admin]] as [$response, $enabled]) {
            if ($enabled) {
                $response->assertSee('--font-display: "Poppins"', false)->assertSee('--font-body: "Inter"', false)
                    ->assertSee('https://fonts.bunny.net/css?family=poppins:400,700|inter:400,700&amp;display=swap', false);
            } else {
                $response->assertDontSee('id="site-typography"', false);
            }
        }
    }

    public function test_default_fonts_work_before_settings_are_saved_and_login_keeps_default_fonts(): void
    {
        $this->get(route('home'))->assertSee('--font-display: "Outfit"', false)->assertSee('--font-body: "Plus Jakarta Sans"', false);
        $this->get(route('login'))->assertDontSee('id="site-typography"', false);
        $this->assertDatabaseMissing('integration_settings', ['key' => 'typography']);
    }

    public function test_admin_can_restore_default_fonts_without_loading_external_fonts(): void
    {
        $this->actingAs(User::factory()->admin()->create())->patch(route('admin.settings.typography.update'), $this->fontData())->assertSessionHasNoErrors();
        $this->patch(route('admin.settings.typography.update'), $this->fontData(['version' => 1, 'heading_font' => 'outfit', 'body_font' => 'plus-jakarta-sans']))->assertSessionHasNoErrors();

        $this->get(route('home'))->assertSee('--font-display: "Outfit"', false)->assertDontSee('https://fonts.bunny.net/css', false);
        $this->assertSame('plus-jakarta-sans', IntegrationSetting::query()->findOrFail('typography')->settings['body_font']);
    }

    public function test_member_font_follows_dashboard_layout_on_shared_article_routes(): void
    {
        $this->actingAs(User::factory()->admin()->create())->patch(route('admin.settings.typography.update'), $this->fontData(['member' => '1']))->assertSessionHasNoErrors();

        $this->actingAs(User::factory()->create())->get(route('articles.index'))->assertSee('--font-body: "Inter"', false);
        $this->app['auth']->forgetGuards();
        $this->get(route('articles.index'))->assertDontSee('id="site-typography"', false);
    }
}
