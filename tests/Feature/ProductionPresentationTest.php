<?php

namespace Tests\Feature;

use App\Enums\MaterialStatus;
use App\Enums\UserStatus;
use App\Models\Exam;
use App\Models\Material;
use App\Models\Package;
use App\Models\Question;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductionPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_use_the_current_contact_without_demo_credentials_or_unfinished_links(): void
    {
        foreach (['home', 'login', 'register', 'password.request'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('Hubungi Kami')->assertDontSee('087867876797')
                ->assertSee('https://wa.me/6287867876797', false)
                ->assertDontSee('Akun demo')->assertDontSee('Prototipe')->assertDontSee('Prototype')
                ->assertDontSee('TBD')->assertDontSee('6282285656792')->assertDontSee('href="#"', false);
        }
    }

    public function test_policy_links_open_dialogs_and_legacy_urls_redirect_to_the_home_popup(): void
    {
        $this->get(route('register'))->assertSee('data-legal-open="terms"', false)
            ->assertSee('data-legal-open="privacy"', false)
            ->assertSee('<dialog class="legal-dialog no-print" id="legal-terms"', false)
            ->assertSee('<dialog class="legal-dialog no-print" id="legal-privacy"', false)
            ->assertSee('lebih dari Rp10.000')->assertSee('transfer ganda')
            ->assertSee('Hak dan permintaan Pengguna')->assertSee('password dalam bentuk hash')
            ->assertSee('name="terms" value="1" required', false)
            ->assertDontSee('notifikasi push')->assertDontSee('login Google');
        $this->get(route('terms'))->assertRedirect(rtrim(route('home'), '/').'/#syarat-ketentuan');
        $this->get(route('privacy'))->assertRedirect(rtrim(route('home'), '/').'/#kebijakan-privasi');
    }

    public function test_home_statistics_and_materials_come_from_the_published_catalogue(): void
    {
        User::factory()->create();
        User::factory()->inactive()->create();
        User::factory()->admin()->create();
        Question::factory()->published()->create();
        Question::factory()->create();
        Exam::factory()->tryout()->create(['question_count' => 110]);
        Exam::factory()->tryout()->create(['status' => 'draft']);
        $material = Material::factory()->published()->create(['title' => 'Materi Publik Terbaru']);
        Material::factory()->draft()->create(['title' => 'Materi Belum Terbit']);

        $this->get(route('home'))->assertSee($material->title)->assertDontSee('Materi Belum Terbit')
            ->assertDontSee('12.480')->assertDontSee('3.200+')->assertDontSee('Dewi Anggraini')
            ->assertViewHas('participantCount', 1)->assertViewHas('questionCount', 1)->assertViewHas('tryoutCount', 1)
            ->assertViewHas('materialCount', 1);
    }

    public function test_member_and_admin_pages_keep_support_contact_and_remove_fake_notification_counts(): void
    {
        $this->actingAs(User::factory()->create())->get(route('dashboard'))
            ->assertSee('Hubungi Kami')->assertDontSee('087867876797')->assertDontSee('appbar__dot', false)->assertDontSee('v0.1');
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard'))
            ->assertSee('Hubungi Kami')->assertDontSee('087867876797')->assertDontSee('appbar__dot', false)->assertDontSee('v0.1');
    }

    public function test_home_only_links_to_checkout_when_the_package_content_is_available(): void
    {
        $unavailable = Package::factory()->create();
        $available = Package::factory()->create();
        $available->exams()->attach(Exam::factory()->tryout()->create());

        $this->get(route('home'))->assertSee('Hubungi Kami')
            ->assertDontSee(route('checkout', ['package' => $unavailable->id]), false)
            ->assertSee(route('checkout', ['package' => $available->id]), false);
    }

    public function test_preparation_preserves_real_content_and_accounts_and_can_be_repeated(): void
    {
        Storage::fake('local');
        User::factory()->admin()->create(['email' => 'owner@ganeshanusa.id', 'password' => Hash::make('Unique-owner-password-73')]);
        $sampleUser = User::factory()->create(['email' => 'peserta@lontar.asia']);
        $changedUser = User::factory()->create(['email' => 'personal@example.com', 'password' => Hash::make('Changed-password-95')]);
        $samplePdf = Material::factory()->published()->create(['file_path' => 'materials/pdf/sample.pdf']);
        Storage::disk('local')->put($samplePdf->file_path, '%PDF-1.4 PLACEHOLDER-LONTAR-v1');
        $realPdf = Material::factory()->published()->create(['file_path' => 'materials/pdf/real.pdf']);
        Storage::disk('local')->put($realPdf->file_path, '%PDF-1.4 Materi pembelajaran yang diunggah admin');
        $sampleVideo = Material::factory()->published()->video()->create(['video_url' => 'https://developer.mozilla.org/shared-assets/videos/flower.mp4']);

        $this->artisan('app:prepare-production-content')->assertSuccessful();
        $this->assertSame(MaterialStatus::Published, $samplePdf->fresh()->status);
        $this->artisan('app:prepare-production-content', ['--apply' => true])->assertSuccessful();

        $this->assertSame(MaterialStatus::Draft, $samplePdf->fresh()->status);
        $this->assertSame(MaterialStatus::Draft, $sampleVideo->fresh()->status);
        $this->assertSame(MaterialStatus::Published, $realPdf->fresh()->status);
        $this->assertSame(UserStatus::Inactive, $sampleUser->fresh()->status);
        $this->assertSame(UserStatus::Active, $changedUser->fresh()->status);
        Storage::disk('local')->assertExists($samplePdf->file_path);
        $backups = Storage::disk('local')->files();
        $this->assertCount(1, $backups);
        $this->artisan('app:prepare-production-content', ['--apply' => true])->assertSuccessful();
        $this->assertSame($backups, Storage::disk('local')->files());
    }

    public function test_preparation_will_not_disable_the_last_active_administrator(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create(['email' => 'admin@lontar.asia', 'phone' => '0812 3456 7890']);

        $this->artisan('app:prepare-production-content', ['--apply' => true])->assertSuccessful();

        $this->assertSame(UserStatus::Active, $admin->fresh()->status);
        $this->assertSame('087867876797', $admin->fresh()->phone);
    }

    public function test_demo_account_seeders_do_not_create_accounts_in_production(): void
    {
        $this->app->instance('env', 'production');

        $this->artisan('db:seed', ['--class' => AdminUserSeeder::class, '--force' => true])->assertSuccessful();
        $this->artisan('db:seed', ['--class' => UserSeeder::class, '--force' => true])->assertSuccessful();

        $this->assertDatabaseCount('users', 0);
    }
}
