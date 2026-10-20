<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\ExamCatalog;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ExamQuestionOrderTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function payload(Exam $exam, array $questionIds, array $overrides = []): array
    {
        return array_replace([
            'title' => $exam->title, 'slug' => $exam->slug, 'kind' => $exam->kind,
            'status' => 'published', 'question_count' => $exam->question_count,
            'duration_minutes' => $exam->duration_minutes, 'points_per_correct' => $exam->points_per_correct,
            'penalty_wrong' => 0, 'random_questions' => false, 'random_options' => false,
            'question_ids' => $questionIds,
        ], $overrides);
    }

    #[DataProvider('sessionKinds')]
    public function test_admin_can_create_and_reorder_questions_independently_for_each_session(string $kind, string $routePrefix): void
    {
        $questions = Question::factory()->published()->count(3)->create();
        $exam = Exam::factory()->make(['kind' => $kind, 'duration_minutes' => $kind === 'quiz' ? 0 : 30, 'question_count' => 2]);
        $order = [$questions[2]->id, $questions[0]->id, $questions[1]->id];
        $other = Exam::factory()->create(['question_count' => 3, 'random_questions' => false]);
        $other->questions()->attach($questions->modelKeys());

        $this->actingAs(User::factory()->admin()->create())
            ->post(route($routePrefix.'.store'), $this->payload($exam, $order))->assertSessionHasNoErrors();

        $saved = Exam::where('slug', $exam->slug)->sole();
        $this->assertSame($order, $saved->questions()->pluck('questions.id')->all());
        foreach ($order as $position => $questionId) {
            $this->assertDatabaseHas('exam_question', ['exam_id' => $saved->id, 'question_id' => $questionId, 'position' => $position + 1]);
        }
        $this->assertSame(array_slice($order, 0, 2), array_column($saved->definition()['questions'], 'id'));

        $reversed = array_reverse($order);
        $this->put(route($routePrefix.'.update', $saved), $this->payload($saved, array_map('strval', $reversed)))->assertSessionHasNoErrors();

        $this->get(route($routePrefix.'.edit', $saved))->assertSee('Urutan soal dipilih')->assertSee('data-question-order-handle', false)
            ->assertViewHas('selected', $reversed)
            ->assertViewHas('questions', fn ($available): bool => array_slice($available->modelKeys(), 0, 3) === $reversed);
        $this->assertSame(array_slice($reversed, 0, 2), array_column($saved->fresh()->definition()['questions'], 'id'));
        $this->assertSame($questions->modelKeys(), $other->questions()->pluck('questions.id')->all());
    }

    public static function sessionKinds(): array
    {
        return ['latihan' => ['quiz', 'admin.materials.quizzes'], 'tryout' => ['tryout', 'admin.exams']];
    }

    public function test_existing_attempt_keeps_its_snapshot_after_admin_reorders_questions(): void
    {
        $questions = Question::factory()->published()->count(3)->create();
        $exam = Exam::factory()->create(['question_count' => 3, 'random_questions' => false, 'random_options' => false]);
        $exam->questions()->attach($questions->modelKeys());
        $reader = User::factory()->create();
        $attempt = ExamAttempt::resumeOrStart($reader, $exam);
        $snapshot = $attempt->question_snapshot;
        $reversed = array_reverse($questions->modelKeys());

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.materials.quizzes.update', $exam), $this->payload($exam, $reversed))->assertSessionHasNoErrors();

        $this->assertSame($snapshot, ExamAttempt::resumeOrStart($reader, $exam->fresh())->question_snapshot);
        $this->assertSame($questions->modelKeys(), array_column($attempt->fresh()->question_snapshot['questions'], 'id'));
        $nextReader = User::factory()->create();
        $this->actingAs($nextReader)->post(route('quizzes.start', $exam->slug))->assertRedirect();
        $nextAttempt = ExamAttempt::where('user_id', $nextReader->id)->sole();
        $this->assertSame($reversed, array_column($nextAttempt->question_snapshot['questions'], 'id'));
    }

    public function test_legacy_order_is_preserved_and_newly_assigned_questions_follow_positioned_questions(): void
    {
        $questions = Question::factory()->published()->count(4)->create();
        $exam = Exam::factory()->create(['question_count' => 4, 'random_questions' => false]);
        $exam->questions()->attach([$questions[2]->id, $questions[1]->id, $questions[0]->id]);

        $this->assertSame(array_slice($questions->modelKeys(), 0, 3), $exam->questions()->pluck('questions.id')->all());

        $exam->questions()->updateExistingPivot($questions[2]->id, ['position' => 1]);
        $exam->questions()->updateExistingPivot($questions[0]->id, ['position' => 2]);
        $questions[3]->exams()->attach($exam->id);

        $expected = [$questions[2]->id, $questions[0]->id, $questions[1]->id, $questions[3]->id];
        $this->assertSame($expected, array_column($exam->definition()['questions'], 'id'));
    }

    public function test_unavailable_questions_are_skipped_before_taking_the_requested_number(): void
    {
        $draft = Question::factory()->create();
        $questions = Question::factory()->published()->count(3)->create();
        $exam = Exam::factory()->create(['question_count' => 2, 'random_questions' => false]);
        $order = [$draft->id, $questions[2]->id, $questions[0]->id, $questions[1]->id];

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.materials.quizzes.update', $exam), $this->payload($exam, $order))->assertSessionHasNoErrors();

        $this->assertSame([$questions[2]->id, $questions[0]->id], array_column($exam->fresh()->definition()['questions'], 'id'));
    }

    public function test_invalid_duplicate_order_does_not_change_the_saved_order(): void
    {
        $questions = Question::factory()->published()->count(2)->create();
        $exam = Exam::factory()->create(['question_count' => 2]);
        $exam->questions()->attach([$questions[0]->id => ['position' => 2], $questions[1]->id => ['position' => 1]]);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.materials.quizzes.update', $exam), $this->payload($exam, [$questions[0]->id, $questions[0]->id]))
            ->assertSessionHasErrors('question_ids.0');

        $this->assertSame([$questions[1]->id, $questions[0]->id], $exam->questions()->pluck('questions.id')->all());
        $this->assertDatabaseHas('exam_question', ['exam_id' => $exam->id, 'question_id' => $questions[0]->id, 'position' => 2]);
    }

    public function test_validation_error_preserves_the_submitted_question_order_in_the_editor(): void
    {
        $questions = Question::factory()->published()->count(3)->create();
        $exam = Exam::factory()->create(['question_count' => 3]);
        $exam->questions()->attach($questions->modelKeys());
        $order = [$questions[2]->id, $questions[0]->id, $questions[1]->id];
        $editUrl = route('admin.materials.quizzes.edit', $exam);

        $this->actingAs(User::factory()->admin()->create())->from($editUrl)
            ->put(route('admin.materials.quizzes.update', $exam), $this->payload($exam, $order, ['title' => '']))->assertSessionHasErrors('title');

        $this->get($editUrl)->assertViewHas('questions', fn ($available): bool => $available->modelKeys() === $order);
        $this->assertSame($questions->modelKeys(), $exam->questions()->pluck('questions.id')->all());
    }

    public function test_tryout_passing_score_and_maximum_score_follow_the_first_questions_in_manual_order(): void
    {
        $binary = Question::factory()->published()->create();
        $weighted = Question::factory()->published()->weighted()->for(Section::factory()->create(['code' => 'TKP']))->create();
        $exam = Exam::factory()->tryout()->create(['question_count' => 1, 'points_per_correct' => 7, 'random_questions' => false]);
        $exam->questions()->attach([$binary->id => ['position' => 1], $weighted->id => ['position' => 2]]);
        $this->actingAs(User::factory()->admin()->create());

        $this->put(route('admin.exams.update', $exam), $this->payload($exam, [$weighted->id, $binary->id], ['passing_score' => 6]))
            ->assertSessionHasErrors('passing_score');
        $this->assertSame(7, $exam->fresh()->maximumScore());

        $this->put(route('admin.exams.update', $exam), $this->payload($exam, [$weighted->id, $binary->id], ['passing_score' => 5]))->assertSessionHasNoErrors();
        $this->assertSame(5, $exam->fresh()->maximumScore());

        $this->put(route('admin.exams.update', $exam), $this->payload($exam, [$weighted->id, $binary->id], ['passing_score' => 6, 'random_questions' => true]))->assertSessionHasNoErrors();
        $this->assertSame(7, $exam->fresh()->maximumScore());
    }

    public function test_manual_tryout_catalog_uses_ordered_scores_without_queries_for_each_card(): void
    {
        $binary = Question::factory()->published()->create();
        $weighted = Question::factory()->published()->weighted()->for(Section::factory()->create(['code' => 'TKP']))->create();
        $first = Exam::factory()->tryout()->create(['points_per_correct' => 7, 'random_questions' => false]);
        $second = Exam::factory()->tryout()->create(['points_per_correct' => 7, 'random_questions' => false]);
        $first->questions()->attach([$weighted->id => ['position' => 1], $binary->id => ['position' => 2]]);
        $second->questions()->attach([$binary->id => ['position' => 1], $weighted->id => ['position' => 2]]);
        $listing = app(ExamCatalog::class)->listing(User::factory()->create(), 'tryout');

        DB::enableQueryLog();
        try {
            $scores = $listing['banks']->map(fn (array $bank): int => $bank['exam']->maximumScore())->all();

            $this->assertSame([7, 5], $scores);
            $this->assertEmpty(DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
        }
    }
}
