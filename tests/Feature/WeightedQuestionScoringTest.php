<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\QuestionScoring;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WeightedQuestionScoringTest extends TestCase
{
    use RefreshDatabase;

    private function payload(Section $section, array $overrides = []): array
    {
        return array_replace(['title' => 'Sikap pelayanan TKP', 'section_id' => $section->id,
            'body' => '<p>Apa tindakan yang kamu pilih?</p>', 'options' => ['Aksi A', 'Aksi B', 'Aksi C', 'Aksi D', 'Aksi E'],
            'scoring_type' => 'weighted', 'option_scores' => [3, 1, 5, 2, 4],
            'explanation' => '<p>Pembahasan khusus peserta berbayar.</p>', 'difficulty' => 'medium', 'status' => 'published'], $overrides);
    }

    public function test_admin_can_manage_weighted_question_inline_and_switch_back_to_binary(): void
    {
        $section = Section::factory()->create(['code' => 'TKP']);
        $data = $this->payload($section);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->postJson(route('admin.questions.store'), $data)->assertCreated()
            ->assertJsonPath('question.option_scores', [3, 1, 5, 2, 4])->assertJsonPath('question.correct_option', null);
        $question = Question::findOrFail($response->json('question.id'));
        $this->assertSame(QuestionScoring::Weighted, $question->scoring_type);
        $this->getJson(route('admin.questions.show', $question))->assertJsonPath('question.scoring_type', 'weighted');

        $this->putJson(route('admin.questions.update', $question), array_replace($data, ['scoring_type' => 'binary', 'correct_option' => 2]))
            ->assertOk()->assertJsonPath('question.option_scores', null)->assertJsonPath('question.correct_option', 2);
        $this->assertFalse($question->fresh()->isWeighted());
    }

    #[DataProvider('invalidScores')]
    public function test_rejects_incomplete_or_out_of_range_scores_without_creating_question(array $scores, string $field): void
    {
        $section = Section::factory()->create(['code' => 'TKP']);

        $this->actingAs(User::factory()->admin()->create())->postJson(route('admin.questions.store'),
            $this->payload($section, ['option_scores' => $scores]))->assertUnprocessable()
            ->assertJsonValidationErrors($field);

        $this->assertDatabaseCount('questions', 0);
    }

    public static function invalidScores(): array
    {
        return ['missing one' => [[1, 2, 3, 5], 'option_scores'], 'below range' => [[0, 2, 3, 4, 5], 'option_scores.0'],
            'above range' => [[1, 2, 3, 4, 6], 'option_scores.4'], 'fraction' => [[1, 2, 3.5, 4, 5], 'option_scores.2'], 'no five' => [[1, 2, 3, 4, 4], 'option_scores']];
    }

    public function test_weighted_scoring_is_restricted_to_tkp_and_threshold_uses_weighted_maximum(): void
    {
        $otherSection = Section::factory()->create(['code' => 'TIU']);
        $this->actingAs(User::factory()->admin()->create())->postJson(route('admin.questions.store'), $this->payload($otherSection))
            ->assertUnprocessable()->assertJsonValidationErrors('scoring_type');
        $this->assertDatabaseCount('questions', 0);
        $section = Section::factory()->create(['code' => 'TKP']);
        $question = Question::factory()->published()->weighted()->for($section)->create();

        $this->postJson(route('admin.exams.store'), ['title' => 'TKP', 'slug' => 'tkp-threshold', 'kind' => 'tryout',
            'question_count' => 1, 'duration_minutes' => 20, 'points_per_correct' => 7, 'penalty_wrong' => 2,
            'passing_score' => 6, 'random_questions' => false, 'random_options' => false, 'status' => 'published',
            'question_ids' => [$question->id]])->assertUnprocessable()->assertJsonValidationErrors('passing_score');
        $this->assertDatabaseCount('exams', 0);

        $quiz = ['title' => 'Latihan TKP', 'slug' => 'latihan-tkp-threshold', 'kind' => 'quiz',
            'question_count' => 1, 'duration_minutes' => 0, 'points_per_correct' => 7, 'penalty_wrong' => 2,
            'passing_score' => 6, 'random_questions' => false, 'random_options' => false, 'status' => 'published',
            'question_ids' => [$question->id]];
        $this->post(route('admin.materials.quizzes.store'), $quiz)
            ->assertSessionHasErrors(['passing_score' => 'Nilai ambang tidak boleh melebihi skor maksimal sesi.']);
        $this->assertDatabaseCount('exams', 0);
        $this->post(route('admin.materials.quizzes.store'), array_replace($quiz, ['passing_score' => 5]))
            ->assertSessionHasNoErrors();
        $this->assertSame(5, Exam::sole()->maximumScore());
    }

    #[DataProvider('weightedAnswers')]
    public function test_tryout_awards_option_points_and_zero_for_blank_without_correct_wrong_classification(?int $choice, int $expected): void
    {
        $section = Section::factory()->create(['code' => 'TKP']);
        $question = Question::factory()->published()->weighted()->for($section)->create();
        $exam = Exam::factory()->tryout()->create(['question_count' => 1, 'points_per_correct' => 7, 'penalty_wrong' => 3, 'random_options' => false]);
        $exam->questions()->attach($question);
        $user = User::factory()->create();
        $attempt = ExamAttempt::resumeOrStart($user, $exam);

        $this->actingAs($user)->patchJson(route('exam-attempts.save', $attempt), ['answers' => [$question->id => $choice], 'flags' => [], 'current' => 0])->assertOk();
        $this->postJson(route('exam-attempts.submit', $attempt))->assertOk();

        $attempt->refresh();
        $this->assertSame($expected, $attempt->score);
        $this->assertSame(5, $attempt->max_score);
        $this->assertSame([0, 0], [$attempt->correct_count, $attempt->wrong_count]);
        $this->assertSame($choice === null ? 0 : 1, $attempt->weighted_answered_count);
        $this->assertSame($choice === null ? 1 : 0, $attempt->blank_count);
    }

    public static function weightedAnswers(): array
    {
        return ['one point' => [0, 1], 'two points' => [1, 2], 'three points' => [2, 3],
            'four points' => [3, 4], 'five points' => [4, 5], 'blank' => [null, 0]];
    }

    #[DataProvider('examKinds')]
    public function test_mixed_sessions_combine_binary_points_penalties_and_tkp_option_scores(string $kind): void
    {
        $section = Section::factory()->create(['code' => 'TKP']);
        $ordinary = Question::factory()->published()->count(2)->create();
        $weighted = Question::factory()->published()->weighted()->for($section)->count(2)->create();
        $exam = Exam::factory()->create(['kind' => $kind, 'question_count' => 4, 'points_per_correct' => 7,
            'penalty_wrong' => 2, 'random_options' => false, 'random_questions' => false]);
        $exam->questions()->attach($ordinary->concat($weighted)->modelKeys());
        $attempt = ExamAttempt::resumeOrStart(User::factory()->create(), $exam);
        $attempt->update(['answers' => [$ordinary[0]->id => 1, $ordinary[1]->id => 0, $weighted[0]->id => 3]]);

        $attempt->complete();

        $this->assertSame([9, 24, 1, 1, 1, 1], [$attempt->score, $attempt->max_score, $attempt->correct_count,
            $attempt->wrong_count, $attempt->weighted_answered_count, $attempt->blank_count]);
        $tkp = $attempt->sectionBreakdown()['TKP'];
        $this->assertSame([4, 10, 1, 2], [$tkp['score'], $tkp['max'], $tkp['weighted'], $tkp['weighted_total']]);
    }

    /** @return array<string, array{string}> */
    public static function examKinds(): array
    {
        return ['quiz' => ['quiz'], 'tryout' => ['tryout']];
    }

    public function test_shuffled_scores_follow_options_snapshots_survive_edits_and_active_client_has_no_scores(): void
    {
        $section = Section::factory()->create(['code' => 'TKP']);
        $question = Question::factory()->published()->weighted()->for($section)->create();
        $exam = Exam::factory()->tryout()->create(['random_options' => true]);
        $exam->questions()->attach($question);
        $user = User::factory()->create();
        $attempt = ExamAttempt::resumeOrStart($user, $exam);
        $snapshot = $attempt->question_snapshot['questions'][0];
        $expected = ['18' => 1, '20' => 2, '22' => 3, '24' => 4, '26' => 5];
        foreach ($snapshot['options'] as $index => $text) {
            $this->assertSame($expected[$text], $snapshot['optionScores'][$index]);
        }
        $this->assertArrayNotHasKey('optionScores', $attempt->clientConfig()['questions'][0]);
        $this->assertArrayNotHasKey('answer', $attempt->clientConfig()['questions'][0]);
        $this->actingAs($user)->get($attempt->attemptUrl())->assertDontSee('"optionScores":', false)->assertDontSee('"explainHtml":', false);
        $chosen = array_search('24', $snapshot['options'], true);
        $question->update(['option_scores' => [5, 4, 3, 2, 1]]);
        $attempt->update(['answers' => [$question->id => $chosen]]);

        $attempt->complete();

        $this->assertSame(4, $attempt->score);
        $this->get($attempt->resultUrl())->assertSee('Skor 4/5')->assertDontSee('Jawaban benar:')->assertDontSee($question->safeExplanation(), false);
    }

    public function test_quiz_keeps_weighted_points_on_results_dashboard_history_and_statistics(): void
    {
        $section = Section::factory()->create(['code' => 'TKP']);
        $question = Question::factory()->published()->weighted()->for($section)->create();
        $exam = Exam::factory()->create(['random_options' => false]);
        $exam->questions()->attach($question);
        $user = User::factory()->create();
        $attempt = ExamAttempt::resumeOrStart($user, $exam);
        $attempt->update(['answers' => [$question->id => 3]]);

        $attempt->complete();

        $this->assertSame([4, 5], [$attempt->score, $attempt->max_score]);
        $summary = ExamAttempt::summaryFor($user);
        $this->assertNull($summary['accuracy']);
        $this->assertSame(1, $summary['weighted']);
        $this->actingAs($user)->get(route('statistics.index', ['kind' => 'quiz']))
            ->assertViewHas('sectionChart', fn (array $chart): bool => $chart['values'] === [4] && $chart['max'] === 5)->assertSee('Berbobot dijawab');
        $this->get(route('dashboard'))->assertSee('4 / 5 poin');
        $this->get(route('history.index'))->assertSee('1 berbobot dijawab')->assertSee('4 / 5');
        $this->get($attempt->resultUrl())->assertSee('Skor 4/5');
    }

    public function test_admin_preview_shows_tkp_option_scores_without_wrong_answer_feedback(): void
    {
        $section = Section::factory()->create(['code' => 'TKP']);
        $question = Question::factory()->published()->weighted()->for($section)->create();

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.questions.show', $question))
            ->assertSee('Skor 4/5')->assertSee('Skor &amp; Pembahasan', false)
            ->assertSee($question->safeExplanation(), false)->assertDontSee('Jawabanmu belum tepat');
    }
}
