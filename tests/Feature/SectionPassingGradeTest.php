<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ExamAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class SectionPassingGradeTest extends TestCase
{
    use RefreshDatabase;

    public function test_exact_section_thresholds_pass_and_are_shown_on_the_result_and_certificate(): void
    {
        $user = User::factory()->create();
        $attempt = ExamAttempt::factory()->passingTryout()->for($user)->create();

        $this->actingAs($user)->postJson(route('exam-attempts.submit', $attempt))->assertOk();

        $attempt->refresh();
        $this->assertSame([311, 315], [$attempt->score, $attempt->max_score]);
        $this->assertSame([65, 80, 166], array_column($attempt->sectionGrades(), 'score'));
        $this->assertSame([65, 80, 166], array_column($attempt->sectionGrades(), 'passing_score'));
        $this->assertSame([true, true, true], array_column($attempt->sectionGrades(), 'passed'));
        $this->assertTrue($attempt->hasPassed());
        $this->assertTrue($attempt->canPrintCertificate());
        $this->assertSame(65, $attempt->section_scores['TWK']['score']);
        $this->get($attempt->resultUrl())->assertSee('Lulus')->assertSee('PASS')->assertDontSee('FAIL')
            ->assertSee('Passing grade: 65')->assertSee('Passing grade: 80')->assertSee('Passing grade: 166')
            ->assertSee('Cetak Sertifikat');
        $this->get(route('certificates.show', $attempt))
            ->assertSee('Passing Grade: 65')->assertSee('Passing Grade: 80')->assertSee('Passing Grade: 166')
            ->assertSee('Ketiga bidang wajib PASS.');
    }

    #[DataProvider('failingSections')]
    public function test_one_failed_or_missing_section_blocks_graduation_even_with_a_high_total(string $code, bool $missing): void
    {
        $user = User::factory()->create();
        $attempt = ExamAttempt::factory()->passingTryout()->for($user)->create();
        $definition = $attempt->question_snapshot;
        $definition['passingScore'] = 1;
        $answers = $attempt->answers;
        if ($missing) {
            $definition['questions'] = array_values(array_filter($definition['questions'], fn (array $question): bool => $question['section'] !== $code));
        } else {
            $question = collect($definition['questions'])->firstWhere('section', $code);
            if ($code === 'TKP') {
                $answers[$question['id']] = 3;
            } else {
                unset($answers[$question['id']]);
            }
        }
        $attempt->update(['question_snapshot' => $definition, 'answers' => $answers]);

        $this->actingAs($user)->postJson(route('exam-attempts.submit', $attempt), [
            'score' => 999, 'section_scores' => ['TWK' => ['score' => 999], 'TIU' => ['score' => 999], 'TKP' => ['score' => 999]],
        ])->assertOk();

        $attempt->refresh();
        $this->assertGreaterThan(1, $attempt->score);
        $this->assertFalse($attempt->hasPassed());
        $this->assertFalse($attempt->canPrintCertificate());
        $this->assertFalse($attempt->sectionGrades()[$code]['passed']);
        $this->assertSame(! $missing, $attempt->sectionGrades()[$code]['present']);
        $this->get($attempt->resultUrl())->assertSee('Belum Lulus')->assertSee('FAIL')->assertDontSee('Cetak Sertifikat');
        $this->get(route('certificates.show', $attempt))->assertNotFound();
        $this->get(route('certificates.index'))->assertSee('Belum ada sertifikat');
        $this->get(route('history.index'))->assertSee('Belum Lulus')->assertSee($code);
    }

    /** @return array<string, array{string, bool}> */
    public static function failingSections(): array
    {
        return ['TWK below' => ['TWK', false], 'TIU below' => ['TIU', false], 'TKP below' => ['TKP', false],
            'TWK missing' => ['TWK', true], 'TIU missing' => ['TIU', true], 'TKP missing' => ['TKP', true]];
    }

    public function test_custom_total_threshold_cannot_override_a_pass_in_all_three_sections(): void
    {
        $user = User::factory()->create();
        $attempt = ExamAttempt::factory()->passingTryout()->for($user)->create();
        $definition = $attempt->question_snapshot;
        $definition['passingScore'] = 315;
        $attempt->update(['question_snapshot' => $definition]);

        $attempt->complete();

        $this->assertTrue($attempt->hasPassed());
        $this->actingAs($user)->get(route('certificates.show', $attempt))->assertOk();
    }

    public function test_customers_see_the_passing_grade_before_starting_a_tryout(): void
    {
        $this->get(route('home'))->assertSee('TWK ≥ 65 poin')->assertSee('TIU ≥ 80 poin')->assertSee('TKP ≥ 166 poin')
            ->assertSee('Wajib PASS di semua bidang');
        $this->actingAs(User::factory()->create())->get(route('tryouts.index'))
            ->assertSee('TWK ≥ 65 poin')->assertSee('TIU ≥ 80 poin')->assertSee('TKP ≥ 166 poin');
        $this->get(route('certificates.index'))->assertSee('Wajib PASS di semua bidang');
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.exams.create'))
            ->assertSee('TWK ≥ 65 poin')->assertSee('TIU ≥ 80 poin')->assertSee('TKP ≥ 166 poin')
            ->assertDontSee('id="exam-passing"', false);
    }

    public function test_existing_results_are_backfilled_from_the_saved_snapshot_without_changing_total_scores(): void
    {
        $user = User::factory()->create();
        $attempt = ExamAttempt::factory()->passingTryout()->for($user)->create();
        $attempt->complete();
        $before = $attempt->getAttributes();
        unset($before['section_scores']);
        $pending = ExamAttempt::factory()->passingTryout()->for($user)->create();

        $migration = require database_path('migrations/2026_10_20_071254_add_section_scores_to_exam_attempts_table.php');
        $migration->down();
        $migration->up();

        $attempt->refresh();
        $this->assertSame([65, 80, 166], array_column($attempt->sectionGrades(), 'score'));
        $after = $attempt->getAttributes();
        unset($after['section_scores']);
        $this->assertSame($before, $after);
        $this->assertNull($pending->fresh()->section_scores);
        $this->actingAs($user)->get(route('certificates.index'))->assertSee(route('certificates.show', $attempt));
    }

    public function test_a_quiz_does_not_issue_a_certificate_even_if_all_sections_pass(): void
    {
        $user = User::factory()->create();
        $attempt = ExamAttempt::factory()->passingTryout()->for($user)->create(['kind' => 'quiz']);
        $attempt->complete();

        $this->assertFalse($attempt->canPrintCertificate());
        $this->actingAs($user)->get(route('certificates.show', $attempt))->assertNotFound();
        $this->get(route('certificates.index'))->assertSee('Belum ada sertifikat');
    }

    #[TestWith([16])]
    #[TestWith([165])]
    public function test_saved_results_below_166_no_longer_pass_or_appear_in_certificates(int $tkpScore): void
    {
        $user = User::factory()->create();
        $attempt = ExamAttempt::factory()->passingTryout()->for($user)->create();
        $attempt->complete();
        $sections = $attempt->section_scores;
        $sections['TKP']['score'] = $tkpScore;
        $attempt->update(['section_scores' => $sections, 'score' => 145 + $tkpScore]);

        $attempt->refresh();

        $this->assertFalse($attempt->hasPassed());
        $this->assertFalse(ExamAttempt::query()->certifiable()->whereKey($attempt->id)->exists());
        $this->actingAs($user)->get($attempt->resultUrl())
            ->assertSee('Belum Lulus')->assertSee('Passing grade: 166')->assertDontSee('Cetak Sertifikat');
        $this->get(route('history.index'))->assertSee('Belum Lulus');
        $this->get(route('certificates.index'))->assertSee('Belum ada sertifikat');
        $this->get(route('certificates.show', $attempt))->assertNotFound();
        $this->assertSame($tkpScore, $attempt->fresh()->section_scores['TKP']['score']);
    }
}
