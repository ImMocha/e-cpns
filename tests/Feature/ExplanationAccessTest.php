<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ExplanationAccessTest extends TestCase
{
    use RefreshDatabase;

    /** @param array<string, mixed> $attributes */
    #[DataProvider('packageAccessStates')]
    public function test_explanation_access_requires_an_active_paid_package(array $attributes, bool $allowed): void
    {
        $this->travelTo('2026-10-14 12:00:00');
        $user = User::factory()->create();
        UserPackage::factory()->for($user)->create($attributes);

        $this->assertSame($allowed, $user->canViewExplanations());
    }

    /** @return array<string, array{array<string, mixed>, bool}> */
    public static function packageAccessStates(): array
    {
        return [
            'active paid package' => [[], true],
            'free package' => [['price' => 0], false],
            'expires at current time' => [['expires_at' => '2026-10-14 12:00:00'], false],
            'expired paid package' => [['expires_at' => '2026-10-14 11:59:59'], false],
            'future paid package' => [['starts_at' => '2026-10-14 12:00:01'], false],
            'revoked paid package' => [['revoked_at' => '2026-10-14 12:00:00'], false],
            'paid package without expiry' => [['expires_at' => null], true],
            'paid package with exhausted tryouts' => [['tryouts_used' => 2], true],
        ];
    }

    public function test_paid_member_gets_question_explanation_only_after_answering(): void
    {
        $user = User::factory()->create();
        UserPackage::factory()->for($user)->create();
        $question = Question::factory()->published()->create(['explanation' => '<p>Pembahasan khusus peserta berbayar.</p>']);
        $this->actingAs($user);

        $this->get(route('question-bank.show', $question))->assertDontSee('Pembahasan khusus peserta berbayar.')
            ->assertViewHas('explanationHtml', null);
        $this->post(route('question-bank.answer', $question), ['option' => 1, 'revision' => $question->revision()])
            ->assertRedirect(route('question-bank.show', $question))->assertSessionHasNoErrors();
        $this->get(route('question-bank.show', $question))->assertSee('Pembahasan khusus peserta berbayar.')
            ->assertSee('Jawaban B')->assertDontSee('Lihat paket belajar');
    }

    public function test_question_feedback_stops_revealing_explanation_when_package_is_revoked(): void
    {
        $user = User::factory()->create();
        $grant = UserPackage::factory()->for($user)->create();
        $question = Question::factory()->published()->create(['explanation' => '<p>Pembahasan yang aksesnya dicabut.</p>']);
        $this->actingAs($user)->post(route('question-bank.answer', $question), ['option' => 1, 'revision' => $question->revision()]);
        $this->get(route('question-bank.show', $question))->assertSee('Pembahasan yang aksesnya dicabut.');
        $grant->revoke(User::factory()->admin()->create());

        $this->withSession(['question_feedback' => ['question_id' => $question->id, 'user_id' => $user->id,
            'revision' => $question->revision(), 'option' => 1]])
            ->get(route('question-bank.show', $question))->assertDontSee('Pembahasan yang aksesnya dicabut.')
            ->assertSee('Jawaban B')->assertViewHas('explanationHtml', null);
    }

    #[DataProvider('examKinds')]
    public function test_free_member_gets_result_keys_without_plain_or_html_explanations(string $kind): void
    {
        $user = User::factory()->create();
        $attempt = $this->completedAttempt($user, $kind);

        $response = $this->actingAs($user)->get($attempt->resultUrl());

        $response->assertSee('Kunci Jawaban')->assertSee('Jawaban benar:')->assertSee('20')
            ->assertDontSee('Pembahasan tersimpan khusus Premium.')
            ->assertDontSee('Pembahasan Soal')->assertSee('Lihat paket belajar')
            ->assertViewHas('canViewExplanations', false);
        $this->assertArrayNotHasKey('explain', $response['reviewQuestions'][0]);
        $this->assertArrayNotHasKey('explainHtml', $response['reviewQuestions'][0]);
        $this->assertSame('<p>Pembahasan tersimpan khusus Premium.</p>', $attempt->fresh()->question_snapshot['questions'][0]['explainHtml']);
    }

    #[DataProvider('examKinds')]
    public function test_paid_member_can_read_explanation_of_an_earlier_free_attempt(string $kind): void
    {
        $user = User::factory()->create();
        $attempt = $this->completedAttempt($user, $kind);
        UserPackage::factory()->for($user)->create();

        $this->actingAs($user)->get($attempt->resultUrl())->assertSee('Pembahasan Soal')
            ->assertSee('Pembahasan tersimpan khusus Premium.')->assertSee('Jawaban benar:')
            ->assertDontSee('Lihat paket belajar')->assertViewHas('canViewExplanations', true);
    }

    public function test_completed_result_keeps_keys_but_loses_explanation_when_package_expires(): void
    {
        $this->travelTo('2026-10-14 12:00:00');
        $user = User::factory()->create();
        UserPackage::factory()->for($user)->create(['expires_at' => now()->addMinute()]);
        $attempt = $this->completedAttempt($user, 'quiz');
        $this->actingAs($user)->get($attempt->resultUrl())->assertSee('Pembahasan tersimpan khusus Premium.');

        $this->travel(1)->minutes();

        $this->get($attempt->resultUrl())->assertDontSee('Pembahasan tersimpan khusus Premium.')
            ->assertSee('Kunci Jawaban')->assertSee('Jawaban benar:')->assertViewHas('canViewExplanations', false);
    }

    public function test_admin_keeps_full_result_access_without_a_package(): void
    {
        $admin = User::factory()->admin()->create();
        $attempt = $this->completedAttempt($admin, 'tryout');

        $this->actingAs($admin)->get($attempt->resultUrl())->assertSee('Pembahasan tersimpan khusus Premium.')
            ->assertSee('Pembahasan Soal')->assertViewHas('canViewExplanations', true);
    }

    /** @return array<string, array{string}> */
    public static function examKinds(): array
    {
        return ['quiz' => ['quiz'], 'tryout' => ['tryout']];
    }

    private function completedAttempt(User $user, string $kind): ExamAttempt
    {
        $exam = Exam::factory()->create(['kind' => $kind, 'random_questions' => false, 'random_options' => false]);
        $question = Question::factory()->published()->create(['explanation' => '<p>Pembahasan tersimpan khusus Premium.</p>']);
        $exam->questions()->attach($question);
        $attempt = ExamAttempt::resumeOrStart($user, $exam);
        $attempt->update(['answers' => [$question->id => 1]]);
        $attempt->complete();

        return $attempt;
    }
}
