<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RawScoreMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_percentage_results_are_recomputed_from_snapshots_on_all_member_pages(): void
    {
        $user = User::factory()->create();
        $exam = $this->legacyQuiz();
        $attempt = ExamAttempt::resumeOrStart($user, $exam);
        $definition = $attempt->question_snapshot;
        $definition['scoreMode'] = 'percent';
        $ids = array_column($definition['questions'], 'id');
        $attempt->update(['question_snapshot' => $definition, 'answers' => [$ids[0] => 1, $ids[1] => 1],
            'status' => ExamAttempt::COMPLETED, 'submitted_at' => now(), 'score' => 67, 'max_score' => 100,
            'passing_score' => 67, 'correct_count' => 2, 'blank_count' => 1]);
        $tryout = ExamAttempt::factory()->tryout()->for($user)->create([
            'status' => ExamAttempt::COMPLETED, 'submitted_at' => now(), 'score' => 450, 'max_score' => 550,
        ]);
        $tryoutBefore = $tryout->fresh()->getAttributes();
        $exam->questions()->update(['correct_option' => 0]);

        $migration = require database_path('migrations/2026_10_20_050121_convert_quiz_scores_to_raw_points.php');
        $migration->up();

        $attempt->refresh();
        $this->assertSame([10, 15, 10], [$attempt->score, $attempt->max_score, $attempt->passing_score]);
        $this->assertSame('points', $attempt->question_snapshot['scoreMode']);
        $this->assertSame(10, $exam->fresh()->passing_score);
        $this->assertSame($tryoutBefore, $tryout->fresh()->getAttributes());
        $this->assertSame(10, array_sum(array_column($attempt->sectionBreakdown(), 'score')));
        $this->assertSame(15, array_sum(array_column($attempt->sectionBreakdown(), 'max')));

        $this->actingAs($user)->get($attempt->resultUrl())->assertSee('dari 15')->assertSee('Nilai ambang sesi: 10.')
            ->assertDontSee('Hasil dikonversi');
        $this->get(route('quizzes.topics'))->assertSee('10 / 15')
            ->assertViewHas('banks', fn ($banks): bool => $banks->first()['exam']->maximumScore() === 15);
        $this->get(route('history.index', ['kind' => 'quiz']))->assertSee('10 / 15');
        $this->get(route('statistics.index', ['kind' => 'quiz']))
            ->assertViewHas('summary', fn (array $summary): bool => $summary['averageScore'] === 10)
            ->assertViewHas('trendChart', fn (array $chart): bool => $chart['values'] === [10]);
        $this->get(route('dashboard'))
            ->assertViewHas('summary', fn (array $summary): bool => $summary['quizCount'] === 1);
    }

    public function test_in_progress_percentage_snapshot_switches_to_points_before_submission(): void
    {
        $user = User::factory()->create();
        $attempt = ExamAttempt::resumeOrStart($user, $this->legacyQuiz());
        $definition = $attempt->question_snapshot;
        $definition['scoreMode'] = 'percent';
        $ids = array_column($definition['questions'], 'id');
        $attempt->update(['question_snapshot' => $definition, 'answers' => [$ids[0] => 1, $ids[1] => 1]]);

        $migration = require database_path('migrations/2026_10_20_050121_convert_quiz_scores_to_raw_points.php');
        $migration->up();

        $attempt->refresh();
        $this->assertSame(ExamAttempt::IN_PROGRESS, $attempt->status);
        $this->assertNull($attempt->score);
        $this->assertSame(10, $attempt->question_snapshot['passingScore']);
        $this->actingAs($user)->postJson(route('exam-attempts.submit', $attempt))->assertOk();
        $attempt->refresh();
        $this->assertSame([10, 15, 10], [$attempt->score, $attempt->max_score, $attempt->passing_score]);
    }

    private function legacyQuiz(): Exam
    {
        $exam = Exam::factory()->create(['question_count' => 3, 'points_per_correct' => 5,
            'random_questions' => false, 'random_options' => false, 'passing_score' => 67]);
        $exam->questions()->attach(Question::factory()->published()->count(3)->create());

        return $exam;
    }
}
