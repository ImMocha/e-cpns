<?php

namespace Tests\Feature\Member;

use App\Enums\ExamBank;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamAttemptTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_before_starting_an_attempt(): void
    {
        $this->get(route('quizzes.attempt', ExamBank::QuizSilogisme->value))
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('exam_attempts', 0);
    }

    public function test_starting_an_attempt_hides_answer_keys_and_resumes_the_same_session(): void
    {
        $user = User::factory()->create();
        $exam = Exam::factory()->create();
        $exam->questions()->attach(Question::factory()->published()->create());
        $this->actingAs($user)->post(route('quizzes.start', $exam->slug))->assertRedirect();
        $this->get(route('quizzes.attempt', $exam->slug))
            ->assertOk()
            ->assertDontSee('"answer":', false)
            ->assertDontSee('"explain":', false);

        $firstAttempt = ExamAttempt::query()->sole();
        $this->post(route('quizzes.start', $exam->slug))->assertRedirect();
        $this->get(route('quizzes.attempt', $exam->slug))->assertOk();

        $this->assertDatabaseCount('exam_attempts', 1);
        $this->assertSame($firstAttempt->id, ExamAttempt::query()->sole()->id);
    }

    public function test_saved_answers_are_scored_on_the_server_and_result_is_available(): void
    {
        $user = User::factory()->create();
        $attempt = ExamAttempt::resumeOrStart($user, ExamBank::QuizSilogisme);

        $this->actingAs($user)->patchJson(route('exam-attempts.save', $attempt), [
            'answers' => ['1' => 0, '2' => 0],
            'flags' => ['3' => true],
            'current' => 1,
        ])->assertOk();

        $this->actingAs($user)->postJson(route('exam-attempts.submit', $attempt), ['score' => 999])
            ->assertOk()->assertJsonPath('resultUrl', $attempt->resultUrl());

        $attempt->refresh();
        $this->assertSame(ExamAttempt::COMPLETED, $attempt->status);
        $this->assertSame(12, $attempt->score);
        $this->assertSame(96, $attempt->max_score);
        $this->assertSame(1, $attempt->correct_count);
        $this->assertSame(1, $attempt->wrong_count);
        $this->assertSame(6, $attempt->blank_count);

        $this->actingAs($user)->get($attempt->resultUrl())
            ->assertOk()
            ->assertSee('12')
            ->assertSee('Kunci Jawaban');
    }

    public function test_invalid_question_and_option_cannot_be_saved(): void
    {
        $user = User::factory()->create();
        $attempt = ExamAttempt::resumeOrStart($user, ExamBank::QuizSilogisme);

        $this->actingAs($user)->patchJson(route('exam-attempts.save', $attempt), [
            'answers' => ['999' => 0], 'flags' => [], 'current' => 0,
        ])->assertUnprocessable();
        $this->actingAs($user)->patchJson(route('exam-attempts.save', $attempt), [
            'answers' => ['1' => 8], 'flags' => [], 'current' => 0,
        ])->assertUnprocessable();

        $this->assertSame([], $attempt->fresh()->answers);
    }

    public function test_another_participant_cannot_save_submit_or_read_the_attempt(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $attempt = ExamAttempt::resumeOrStart($owner, ExamBank::QuizSilogisme);

        $this->actingAs($other)->patchJson(route('exam-attempts.save', $attempt), [
            'answers' => ['1' => 0], 'flags' => [], 'current' => 0,
        ])->assertNotFound();
        $this->actingAs($other)->postJson(route('exam-attempts.submit', $attempt))->assertNotFound();

        $attempt->complete();
        $this->actingAs($other)->get($attempt->resultUrl())->assertNotFound();
        $this->assertSame([], $attempt->fresh()->answers);
    }

    public function test_expired_tryout_uses_only_saved_answers_and_is_submitted_automatically(): void
    {
        $user = User::factory()->create();
        $attempt = ExamAttempt::factory()->tryout()->create([
            'user_id' => $user->id,
            'answers' => ['1' => 2],
            'started_at' => now()->subMinutes(31),
            'expires_at' => now()->subMinute(),
        ]);

        $this->actingAs($user)->patchJson(route('exam-attempts.save', $attempt), [
            'answers' => ['1' => 0, '2' => 2], 'flags' => [], 'current' => 1,
        ])->assertConflict()->assertJsonPath('resultUrl', $attempt->resultUrl());

        $attempt->refresh();
        $this->assertSame(ExamAttempt::COMPLETED, $attempt->status);
        $this->assertSame(5, $attempt->score);
        $this->assertSame(1, $attempt->correct_count);
        $this->assertTrue($attempt->auto_submitted);
    }

    public function test_submitting_twice_does_not_create_a_second_result(): void
    {
        $user = User::factory()->create();
        $attempt = ExamAttempt::resumeOrStart($user, ExamBank::QuizSilogisme);

        $this->actingAs($user)->postJson(route('exam-attempts.submit', $attempt))->assertOk();
        $this->actingAs($user)->postJson(route('exam-attempts.submit', $attempt))->assertOk();

        $this->assertDatabaseCount('exam_attempts', 1);
        $this->assertSame(0, $attempt->fresh()->score);
    }

    public function test_result_query_requires_a_valid_owned_attempt_id(): void
    {
        $user = User::factory()->create();
        $attempt = ExamAttempt::resumeOrStart($user, ExamBank::QuizSilogisme);
        $attempt->complete();

        $this->actingAs($user)->get(route('quizzes.result', ['quiz' => ExamBank::QuizSilogisme->value, 'attempt' => 0]))
            ->assertNotFound();
        $this->actingAs($user)->get(route('quizzes.result', ['quiz' => ExamBank::QuizSilogisme->value, 'attempt' => 999]))
            ->assertNotFound();
        $this->actingAs($user)->get($attempt->resultUrl())->assertOk();
    }
}
