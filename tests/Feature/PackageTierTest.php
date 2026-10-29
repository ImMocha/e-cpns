<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PackageTier;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Material;
use App\Models\Package;
use App\Models\Question;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PackageTierTest extends TestCase
{
    use RefreshDatabase;

    private function exam(string $kind = 'tryout', string $access = 'premium'): Exam
    {
        $exam = Exam::factory()->create(['kind' => $kind, 'access_level' => $access]);
        $exam->questions()->attach(Question::factory()->published()->create());

        return $exam;
    }

    /** @return array<string, array{PackageTier, int, int, string}> */
    public static function limitedPackages(): array
    {
        return [
            'regular' => [PackageTier::Regular, 1, 2, 'none'],
            'bundling' => [PackageTier::Bundling, 3, 3, 'local'],
        ];
    }

    #[DataProvider('limitedPackages')]
    public function test_admin_saves_limited_packages_without_content_and_assigns_all_tokens(PackageTier $tier, int $count, int $attempts, string $leaderboard): void
    {
        $this->actingAs(User::factory()->admin()->create())->post(route('admin.packages.store'), [
            'tier' => $tier->value, 'name' => $tier->label(), 'price' => 15000,
            'duration_days' => 365, 'position' => 1, 'is_active' => true,
            'exam_ids' => [], 'tryout_quota' => 999, 'tryout_tokens' => 999, 'leaderboard_access' => 'national',
        ])->assertSessionHasNoErrors();

        $package = Package::sole();
        $this->assertDatabaseHas('packages', ['id' => $package->id, 'tier' => $tier->value,
            'tryout_quota' => $attempts, 'tryout_tokens' => $count, 'leaderboard_access' => $leaderboard]);
        $this->get(route('admin.packages.edit', $package))->assertOk()->assertSee('Jenis paket dan benefit');
    }

    #[DataProvider('limitedPackages')]
    public function test_limited_packages_use_tokens_instead_of_attached_content(PackageTier $tier, int $count, int $attempts, string $leaderboard): void
    {
        $material = Material::factory()->premium()->video()->create();
        $practice = $this->exam('practice');
        $this->actingAs(User::factory()->admin()->create());
        $data = ['tier' => $tier->value, 'name' => $tier->label(), 'price' => 15000,
            'position' => 1, 'is_active' => false];

        $this->post(route('admin.packages.store'), $data + ['material_ids' => [$material->id], 'exam_ids' => [$practice->id]])
            ->assertSessionHasErrors(['material_ids', 'exam_ids']);
        $this->post(route('admin.packages.store'), $data + ['exam_ids' => Exam::factory()->tryout()->count($count + 1)->create()->modelKeys()])
            ->assertSessionHasErrors('exam_ids');
        $this->assertDatabaseCount('packages', 0);
    }

    #[DataProvider('limitedPackages')]
    public function test_limited_members_can_browse_pages_while_premium_content_and_actions_remain_locked(PackageTier $tier, int $count, int $attempts, string $leaderboard): void
    {
        Storage::fake(Material::PDF_DISK);
        $user = User::factory()->create();
        $grant = UserPackage::factory()->for($user)->create(['tier' => $tier]);
        $material = Material::factory()->create();
        Storage::disk(Material::PDF_DISK)->put($material->file_path, '%PDF-test');
        $practice = $this->exam('practice', 'free');
        $quiz = $this->exam('quiz', 'free');
        $grant->materials()->attach($material);
        $grant->exams()->attach([$practice->id, $quiz->id]);
        $oldPractice = ExamAttempt::factory()->for($user)->create(['kind' => 'practice', 'bank_slug' => $practice->slug]);
        $passed = ExamAttempt::factory()->passingTryout()->for($user)->create();
        $passed->complete();

        $this->actingAs($user)->get(route('materials.show', $material))->assertOk()->assertViewHas('contentAccessible', false);
        $this->get(route('materials.index'))->assertOk()->assertSee($material->topic->name)->assertSee('materi terkunci');
        $this->get(route('quizzes.index'))->assertOk()->assertSee($practice->title)->assertSee('Soal terkunci')
            ->assertDontSee(route('practices.start', $practice->slug))->assertDontSee($oldPractice->attemptUrl());
        $this->get(route('materials.file', $material))->assertForbidden();
        $this->post(route('materials.progress.store', $material))->assertForbidden();
        $this->post(route('practices.start', $practice->slug))->assertForbidden();
        $this->post(route('quizzes.start', $quiz->slug))->assertForbidden();
        $this->get(route('statistics.index'))->assertOk()->assertViewHas('contentAccessible', false)->assertSee('statistik terkunci')->assertDontSee('data-chart=', false);
        $this->get(route('certificates.index'))->assertOk()->assertViewHas('contentAccessible', false)->assertSee($passed->title)->assertDontSee('Lihat &amp; Cetak');
        $this->get(route('certificates.show', $passed))->assertOk()->assertSee('Sertifikat terkunci')->assertDontSee('certificate-document');
        $otherPassed = ExamAttempt::factory()->passingTryout()->create();
        $otherPassed->complete();
        $this->get(route('certificates.show', $otherPassed))->assertNotFound();
        $this->get(route('dashboard'))->assertOk()->assertViewHas('materialCount', 0)->assertDontSee('Perkembangan Nilai')
            ->assertSee('href="'.route('materials.index').'"', false)
            ->assertSee('href="'.route('quizzes.index').'"', false)
            ->assertSee('href="'.route('statistics.index').'"', false)
            ->assertSee('href="'.route('certificates.index').'"', false);
        $this->get(route('tryouts.result', ['tryout' => $passed->bank_slug, 'attempt' => $passed->id]))
            ->assertOk()->assertDontSee('Cetak Sertifikat');
        $this->assertDatabaseCount('material_progress', 0);
    }

    public function test_premium_unlocks_new_content_and_expiry_locks_it_again(): void
    {
        $this->freezeTime();
        Storage::fake(Material::PDF_DISK);
        $user = User::factory()->create();
        $this->exam();
        $package = Package::factory()->unlimited()->create(['tier' => PackageTier::Premium, 'leaderboard_access' => 'national']);
        $grant = UserPackage::grant($user, $package, User::factory()->admin()->create(), 'Akses Premium');
        $material = Material::factory()->premium()->create();
        Storage::disk(Material::PDF_DISK)->put($material->file_path, '%PDF-test');
        $practice = $this->exam('practice');
        $newTryout = $this->exam();
        UserPackage::factory()->for($user)->create(['tier' => PackageTier::Regular]);

        $this->actingAs($user)->get(route('materials.file', $material))->assertOk();
        $this->post(route('materials.progress.store', $material))->assertRedirect();
        $this->post(route('practices.start', $practice->slug))->assertRedirect();
        $this->get(route('tryouts.index'))->assertViewHas('banks', fn ($banks): bool => $banks->firstWhere('exam.id', $newTryout->id)['canStart']);
        $this->post(route('tryouts.start', $newTryout->slug))->assertRedirect();
        $this->assertDatabaseHas('exam_attempts', ['bank_slug' => $newTryout->slug, 'user_package_id' => $grant->id]);
        $this->get(route('statistics.index'))->assertOk();
        $this->get(route('certificates.index'))->assertOk();
        $this->get(route('dashboard'))->assertOk()->assertViewHas('materialCount', 1);
        $grant->update(['expires_at' => now()]);

        $this->get(route('materials.file', $material))->assertForbidden();
        $this->post(route('practices.start', $practice->slug))->assertForbidden();
        $this->get(route('statistics.index'))->assertOk()->assertViewHas('contentAccessible', false)->assertSee('statistik terkunci')->assertDontSee('data-chart=', false);
        $this->get(route('certificates.index'))->assertOk()->assertViewHas('contentAccessible', false);
        $this->post(route('tryouts.start', $this->exam()->slug))->assertForbidden();
    }

    public function test_checkout_and_manual_activation_preserve_the_tier_when_catalog_changes(): void
    {
        Http::preventStrayRequests();
        $user = User::factory()->create();
        $this->exam();
        $package = Package::factory()->unlimited()->create(['tier' => PackageTier::Premium, 'price' => 0]);

        $this->actingAs($user)->post(route('checkout.store'), ['package_id' => $package->id])->assertSessionHasNoErrors();
        $transaction = Transaction::sole();
        $this->assertSame('premium', $transaction->package_snapshot['tier']);
        $this->assertDatabaseHas('user_packages', ['id' => $transaction->user_package_id, 'tier' => 'premium']);
        $package->update(['tier' => PackageTier::Regular]);
        $this->assertSame(PackageTier::Premium, $transaction->userPackage->tier);
        $this->assertTrue($user->fresh()->canViewExplanations());
        Http::assertNothingSent();
    }

    public function test_package_benefits_are_consistent_across_catalog_and_checkout(): void
    {
        $this->exam();
        $package = Package::factory()->unlimited()->create(['tier' => PackageTier::Premium]);

        $this->get(route('home'))->assertSee('Akses paket soal Tryout SKD unlimited')->assertSee('Sertifikat');
        $this->actingAs(User::factory()->create())->get(route('packages.index'))
            ->assertSee('Materi Lengkap SKD')->assertSee('Progress belajar (analisa pembelajaran)');
        $this->get(route('checkout', ['package' => $package->id]))
            ->assertSee('Akses percobaan unlimited')->assertSee('Perangkingan Nasional')->assertSee('Sertifikat');
    }

    #[DataProvider('limitedPackages')]
    public function test_token_selection_and_total_attempts_follow_the_limited_package(PackageTier $tier, int $count, int $attempts, string $leaderboard): void
    {
        $user = User::factory()->create();
        $exams = collect(range(1, $count))->map(fn (): Exam => $this->exam());
        $package = Package::factory()->create(['tier' => $tier, 'tryout_tokens' => $count, 'tryout_quota' => $attempts]);
        $grant = UserPackage::grant($user, $package, User::factory()->admin()->create(), 'Akses tryout');
        $this->actingAs($user);
        foreach ($exams as $exam) {
            $this->post(route('tryout-tokens.redeem', [$grant, $exam]))->assertSessionHasNoErrors();
        }
        $this->post(route('tryout-tokens.redeem', [$grant, $this->exam()]))->assertSessionHasErrors('exam');

        for ($index = 0; $index < $attempts; $index++) {
            $exam = $exams[$index % $count];
            $this->post(route('tryouts.start', $exam->slug))->assertSessionHasNoErrors()->assertRedirect();
            $attempt = ExamAttempt::query()->whereBelongsTo($user)->latest('id')->firstOrFail();
            $this->post(route('exam-attempts.submit', $attempt))->assertOk();
        }
        $this->post(route('tryouts.start', $exams->first()->slug))->assertSessionHasErrors('exam');
        $this->assertDatabaseCount('exam_attempts', $attempts);
        $this->assertDatabaseHas('user_packages', ['id' => $grant->id, 'tier' => $tier->value, 'tryouts_used' => $attempts, 'tryout_tokens' => 0]);
    }

    public function test_admin_can_activate_premium_without_selecting_content_and_cannot_limit_its_quota(): void
    {
        $this->exam();

        $this->actingAs(User::factory()->admin()->create())->post(route('admin.packages.store'), [
            'tier' => 'premium', 'name' => 'Paket Premium', 'price' => 80000, 'position' => 3, 'is_active' => true,
            'tryout_quota' => 1, 'leaderboard_access' => 'none',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('packages', ['tier' => 'premium', 'tryout_quota' => null, 'tryout_tokens' => 0, 'leaderboard_access' => 'national']);
    }

    #[DataProvider('limitedPackages')]
    public function test_limited_packages_can_be_bought_and_granted_before_any_content_exists(PackageTier $tier, int $count, int $attempts, string $leaderboard): void
    {
        Http::preventStrayRequests();
        $package = Package::factory()->create(['tier' => $tier, 'name' => $tier->label(), 'price' => 0,
            'tryout_tokens' => $count, 'tryout_quota' => $attempts]);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('packages.index'))->assertOk()->assertDontSee('Konten belum tersedia');
        $this->get(route('checkout', ['package' => $package->id]))->assertOk()->assertViewHas('contentAvailable', true);
        $this->post(route('checkout.store'), ['package_id' => $package->id])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('user_packages', ['user_id' => $user->id, 'tier' => $tier->value, 'tryout_tokens' => $count, 'tryout_quota' => $attempts]);
        $manualUser = User::factory()->create();
        UserPackage::grant($manualUser, $package, User::factory()->admin()->create(), 'Akses sebelum konten terbit');
        $this->assertDatabaseHas('user_packages', ['user_id' => $manualUser->id, 'tier' => $tier->value, 'tryout_tokens' => $count]);
        $this->assertDatabaseCount('exams', 0);
        Http::assertNothingSent();
    }

    public function test_conversion_replaces_stale_catalog_content_with_tokens_and_preserves_existing_member_choices(): void
    {
        $package = Package::factory()->create(['tier' => PackageTier::Bundling, 'tryout_tokens' => 0, 'tryout_quota' => 3]);
        $exam = $this->exam();
        $package->exams()->attach($exam);
        $grant = UserPackage::factory()->for($package)->create(['tier' => PackageTier::Bundling]);
        $grant->exams()->attach($exam);
        $pending = Transaction::factory()->for($package)->create(['package_snapshot' => [
            'name' => $package->name, 'duration_days' => 30, 'tryout_quota' => 3, 'tryout_tokens' => 0,
            'materials' => [], 'exams' => [['id' => $exam->id, 'title' => $exam->title]],
        ]]);
        $exam->delete();
        $migration = require database_path('migrations/2026_10_29_064622_convert_regular_and_bundling_packages_to_tryout_choices.php');

        $migration->up();

        $this->assertDatabaseHas('packages', ['id' => $package->id, 'tryout_tokens' => 3]);
        $this->assertDatabaseCount('exam_package', 0);
        $this->assertSame(3, $pending->fresh()->package_snapshot['tryout_tokens']);
        $this->assertSame([], $pending->fresh()->package_snapshot['exams']);
        $this->assertDatabaseHas('exam_user_package', ['user_package_id' => $grant->id, 'exam_id' => $exam->id]);
    }

    public function test_upgrade_updates_existing_catalog_and_entitlements_without_changing_premium_price(): void
    {
        $regular = Package::factory()->create(['name' => 'Paket 1', 'price' => 9999]);
        $bundling = Package::factory()->create(['name' => 'Paket 2', 'price' => 9999]);
        $premium = Package::factory()->create(['name' => 'Paket 3', 'price' => 95000]);
        $regular->materials()->attach(Material::factory()->premium()->video()->create());
        $grant = UserPackage::factory()->for($regular)->create();
        $pending = Transaction::factory()->for($regular)->create();
        $migration = require database_path('migrations/2026_10_29_060337_add_tier_to_packages_and_user_packages.php');
        $migration->down();

        $migration->up();

        $this->assertDatabaseHas('packages', ['id' => $regular->id, 'name' => 'Paket Reguler', 'tier' => 'regular', 'price' => 15000, 'tryout_quota' => 2, 'tryout_tokens' => 1]);
        $this->assertDatabaseHas('packages', ['id' => $bundling->id, 'name' => 'Paket Bundling', 'tier' => 'bundling', 'price' => 35000, 'tryout_quota' => 3, 'tryout_tokens' => 3]);
        $this->assertDatabaseHas('packages', ['id' => $premium->id, 'name' => 'Paket Premium', 'tier' => 'premium', 'price' => 95000, 'tryout_quota' => null]);
        $this->assertDatabaseHas('user_packages', ['id' => $grant->id, 'tier' => 'regular']);
        $this->assertSame('regular', $pending->fresh()->package_snapshot['tier']);
        $this->assertSame(0, $regular->materials()->count());
    }
}
