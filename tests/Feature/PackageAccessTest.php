<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Material;
use App\Models\Package;
use App\Models\Question;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PackageAccessTest extends TestCase
{
    use RefreshDatabase;

    private function exam(string $kind = 'tryout', string $access = 'premium'): Exam
    {
        $exam = Exam::factory()->create(['kind' => $kind, 'access_level' => $access, 'duration_minutes' => $kind === 'tryout' ? 30 : 0]);
        $exam->questions()->attach(Question::factory()->published()->create());

        return $exam;
    }

    /** @param array<string, mixed> $overrides */
    private function grant(User $user, ?Material $material = null, ?Exam $exam = null, array $overrides = []): UserPackage
    {
        $package = Package::factory()->create($overrides);
        if ($material !== null) {
            $package->materials()->attach($material);
        }
        if ($exam !== null) {
            $package->exams()->attach($exam);
        }

        return UserPackage::grant($user, $package, User::factory()->admin()->create(), 'Akses peserta');
    }

    public function test_package_unlocks_only_selected_pdf_and_progress_and_dashboard(): void
    {
        Storage::fake(Material::PDF_DISK);
        $user = User::factory()->create();
        $included = Material::factory()->premium()->create(['file_path' => 'materials/pdf/included.pdf']);
        $excluded = Material::factory()->premium()->create(['file_path' => 'materials/pdf/excluded.pdf']);
        Storage::disk(Material::PDF_DISK)->put($included->file_path, '%PDF-test');
        Storage::disk(Material::PDF_DISK)->put($excluded->file_path, '%PDF-test');
        $this->grant($user, $included);

        $this->actingAs($user)->get(route('materials.show', $included))->assertOk();
        $this->get(route('materials.file', $included))->assertOk();
        $this->get(route('materials.file', $excluded))->assertForbidden();
        $this->get(route('materials.show', $excluded))->assertOk()->assertViewHas('contentAccessible', false);
        $this->get(route('materials.index'))->assertOk()->assertViewHas('counts', fn (array $counts): bool => $counts['locked'] === 1);
        $this->post(route('materials.progress.store', $included))->assertRedirect();
        $this->get(route('dashboard'))->assertViewHas('materialCount', 1)->assertViewHas('completedMaterialCount', 1);

        $this->actingAs(User::factory()->create())->get(route('materials.file', $included))->assertForbidden();
    }

    /** @return array<string, array{string}> */
    public static function unavailableGrants(): array
    {
        return ['expired' => ['expired'], 'revoked' => ['revoked'], 'future activation' => ['future']];
    }

    #[DataProvider('unavailableGrants')]
    public function test_inactive_entitlement_blocks_material_file_and_new_exam(string $state): void
    {
        $this->freezeTime();
        Storage::fake(Material::PDF_DISK);
        $user = User::factory()->create();
        $material = Material::factory()->premium()->create();
        Storage::disk(Material::PDF_DISK)->put($material->file_path, '%PDF-test');
        $exam = $this->exam();
        $grant = $this->grant($user, $material, $exam);
        $grant->update(match ($state) {
            'expired' => ['expires_at' => now()],
            'revoked' => ['revoked_at' => now()],
            'future' => ['starts_at' => now()->addDay()],
        });

        $this->actingAs($user)->get(route('materials.file', $material))->assertForbidden();
        $this->post(route('materials.progress.store', $material))->assertForbidden();
        $this->post(route('tryouts.start', $exam->slug))->assertForbidden();
        $this->get(route('dashboard'))->assertViewHas('materialCount', 0);
        $this->assertDatabaseCount('exam_attempts', 0);
        $this->assertDatabaseHas('user_packages', ['id' => $grant->id, 'tryouts_used' => 0]);
    }

    public function test_entitlement_does_not_expose_drafts_or_inactive_content(): void
    {
        $user = User::factory()->create();
        $material = Material::factory()->premium()->video()->create();
        $grant = $this->grant($user, $material);
        $material->update(['status' => 'draft']);

        $this->actingAs($user)->get(route('materials.show', $material))->assertNotFound();
        $this->get(route('packages.index'))->assertDontSee($material->title);
        $this->get(route('dashboard'))->assertViewHas('materialCount', 0);
        $this->assertTrue($grant->fresh()->isActive());
    }

    public function test_premium_exam_requires_its_own_package(): void
    {
        $user = User::factory()->create();
        $included = $this->exam();
        $excluded = $this->exam();
        $this->grant($user, exam: $included);

        $this->actingAs($user)->post(route('tryouts.start', $excluded->slug))->assertForbidden();
        $this->get(route('tryouts.index'))->assertSee('Memerlukan paket aktif');
        $this->assertDatabaseCount('exam_attempts', 0);
    }

    public function test_new_tryout_consumes_once_resume_does_not_and_quota_is_total_across_tryouts(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        $first = $this->exam();
        $second = $this->exam();
        $grant = $this->grant($user, exam: $first, overrides: ['tryout_quota' => 1]);
        $grant->exams()->attach($second);

        $this->actingAs($user)->post(route('tryouts.start', $first->slug))->assertRedirect();
        $attempt = ExamAttempt::sole();
        $this->assertDatabaseHas('exam_attempts', ['id' => $attempt->id, 'user_package_id' => $grant->id]);
        $this->assertDatabaseHas('user_packages', ['id' => $grant->id, 'tryouts_used' => 1]);
        $this->post(route('tryouts.start', $first->slug))->assertRedirect($attempt->attemptUrl());
        $this->get($attempt->attemptUrl())->assertOk();
        $this->assertDatabaseCount('exam_attempts', 1);
        $this->assertDatabaseHas('user_packages', ['id' => $grant->id, 'tryouts_used' => 1]);
        $this->post(route('tryouts.start', $second->slug))->assertSessionHasErrors('exam');
        $this->post(route('exam-attempts.submit', $attempt))->assertOk();
        $this->post(route('tryouts.start', $first->slug))->assertSessionHasErrors('exam');
        $this->assertDatabaseCount('exam_attempts', 1);
        $this->get($attempt->resultUrl())->assertOk()->assertSee('Lihat sesi &amp; kuota', false);
        $this->get(route('tryouts.index'))->assertSee('Kuota tryout habis');
    }

    public function test_failed_attempt_creation_does_not_consume_quota(): void
    {
        $user = User::factory()->create();
        $exam = Exam::factory()->tryout()->create(['access_level' => 'premium']);
        $grant = $this->grant($user, exam: $exam);

        $this->actingAs($user)->post(route('tryouts.start', $exam->slug))->assertSessionHasErrors('exam');

        $this->assertDatabaseCount('exam_attempts', 0);
        $this->assertDatabaseHas('user_packages', ['id' => $grant->id, 'tryouts_used' => 0]);
    }

    public function test_quiz_and_free_tryout_do_not_consume_package_quota(): void
    {
        $user = User::factory()->create();
        $quiz = $this->exam('quiz');
        $free = $this->exam(access: 'free');
        $grant = $this->grant($user, exam: $quiz, overrides: ['tryout_quota' => 0]);

        $this->actingAs($user)->post(route('quizzes.start', $quiz->slug))->assertRedirect();
        $this->post(route('tryouts.start', $free->slug))->assertRedirect();

        $this->assertDatabaseCount('exam_attempts', 2);
        $this->assertDatabaseHas('user_packages', ['id' => $grant->id, 'tryouts_used' => 0]);
        $this->assertDatabaseHas('exam_attempts', ['bank_slug' => $free->slug, 'user_package_id' => null]);
    }

    public function test_unlimited_package_and_admin_can_start_premium_exam(): void
    {
        $user = User::factory()->create();
        $exam = $this->exam();
        $grant = $this->grant($user, exam: $exam, overrides: ['duration_days' => null, 'tryout_quota' => null]);

        $this->actingAs($user)->post(route('tryouts.start', $exam->slug))->assertRedirect();
        $this->assertDatabaseHas('user_packages', ['id' => $grant->id, 'expires_at' => null, 'tryout_quota' => null, 'tryouts_used' => 1]);
        $this->actingAs(User::factory()->admin()->create())->post(route('tryouts.start', $exam->slug))->assertRedirect();
        $this->assertDatabaseCount('exam_attempts', 2);
    }

    public function test_another_active_package_supplies_quota_when_first_is_exhausted(): void
    {
        $user = User::factory()->create();
        $exam = $this->exam();
        $exhausted = $this->grant($user, exam: $exam, overrides: ['tryout_quota' => 1]);
        $exhausted->update(['tryouts_used' => 1]);
        $available = $this->grant($user, exam: $exam, overrides: ['tryout_quota' => 1]);

        $this->actingAs($user)->post(route('tryouts.start', $exam->slug))->assertRedirect();

        $this->assertDatabaseHas('exam_attempts', ['user_package_id' => $available->id]);
        $this->assertDatabaseHas('user_packages', ['id' => $available->id, 'tryouts_used' => 1]);
        $this->assertDatabaseHas('user_packages', ['id' => $exhausted->id, 'tryouts_used' => 1]);
    }

    public function test_session_started_before_package_expiry_can_finish_and_keep_its_result(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        $exam = $this->exam();
        $grant = $this->grant($user, exam: $exam);
        $this->actingAs($user)->post(route('tryouts.start', $exam->slug))->assertRedirect();
        $attempt = ExamAttempt::sole();
        $grant->update(['expires_at' => now()]);

        $this->post(route('tryouts.start', $exam->slug))->assertRedirect($attempt->attemptUrl());
        $this->post(route('exam-attempts.submit', $attempt))->assertOk();
        $this->get($attempt->resultUrl())->assertOk();
        $this->post(route('tryouts.start', $exam->slug))->assertForbidden();
        $this->assertDatabaseHas('user_packages', ['id' => $grant->id, 'tryouts_used' => 1]);
    }
}
