<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Package;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PracticeSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_practice_page_lists_only_published_standalone_practices(): void
    {
        $practice = $this->practice();
        $quiz = Exam::factory()->for(Topic::factory())->create();
        $tryout = Exam::factory()->tryout()->create();
        $draft = Exam::factory()->practice()->create(['status' => 'draft']);
        $archived = Exam::factory()->practice()->create();
        $archived->delete();
        $this->actingAs(User::factory()->create());

        $this->get(route('quizzes.index'))->assertViewIs('Dashboard.User.quizzes.index')
            ->assertViewHas('kind', 'practice')->assertSee($practice->title)
            ->assertSee(route('practices.start', $practice->slug))
            ->assertDontSee($quiz->title)->assertDontSee($tryout->title)
            ->assertDontSee($draft->title)->assertDontSee($archived->title);
        $this->get(route('quizzes.topics'))->assertSee($quiz->title)->assertDontSee($practice->title);
        $this->get(route('tryouts.index'))->assertSee($tryout->title)->assertDontSee($practice->title);
    }

    public function test_admin_creates_updates_and_archives_practice_in_its_own_workspace(): void
    {
        $question = Question::factory()->published()->create();
        $data = $this->payload($question);
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('admin.practices.create'))->assertSee('Tambah Latihan Soal')
            ->assertDontSee('Topik latihan')->assertDontSee('Passing grade tryout')
            ->assertViewHas('exam', fn (Exam $exam): bool => $exam->kind === 'practice' && $exam->duration_minutes === 30);
        $this->post(route('admin.practices.store'), $data)->assertSessionHasNoErrors();
        $practice = Exam::sole();
        $this->assertSame('practice', $practice->kind);
        $this->assertSame([$question->id], $practice->questions()->pluck('questions.id')->all());
        $this->get(route('admin.practices.index'))->assertSee($practice->title)->assertSee(route('admin.practices.edit', $practice));
        $this->get(route('admin.exams.index'))->assertDontSee($practice->title);
        $this->get(route('admin.materials.quizzes.index'))->assertDontSee($practice->title);
        $this->get(route('admin.exams.edit', $practice))->assertRedirect(route('admin.practices.edit', $practice));
        $this->put(route('admin.practices.update', $practice), array_replace($data, ['duration_minutes' => 45]))
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.practices.edit', $practice));
        $this->assertSame(45, $practice->fresh()->duration_minutes);
        $this->delete(route('admin.exams.destroy', $practice))->assertNotFound();
        $this->assertNotSoftDeleted($practice);
        $this->delete(route('admin.practices.destroy', $practice))->assertRedirect(route('admin.practices.index'));
        $this->assertSoftDeleted($practice);
    }

    public function test_practice_workspace_rejects_other_kinds_topic_gates_and_zero_duration(): void
    {
        $question = Question::factory()->published()->create();
        $data = $this->payload($question);
        $this->actingAs(User::factory()->admin()->create());

        $this->post(route('admin.exams.store'), $data)->assertSessionHasErrors('kind');
        $this->post(route('admin.materials.quizzes.store'), $data)->assertSessionHasErrors('kind');
        $this->post(route('admin.practices.store'), array_replace($data, ['kind' => 'quiz']))->assertSessionHasErrors('kind');
        $this->post(route('admin.practices.store'), array_replace($data, ['duration_minutes' => 0]))->assertSessionHasErrors('duration_minutes');
        $this->post(route('admin.practices.store'), array_replace($data, ['topic_id' => Topic::factory()->create()->id, 'requires_topic_completion' => true]))
            ->assertSessionHasErrors(['topic_id', 'requires_topic_completion']);
        $this->assertDatabaseCount('exams', 0);
    }

    public function test_practice_start_resumes_timer_and_uses_shared_save_submit_and_review(): void
    {
        $this->freezeTime();
        $practice = $this->practice(['passing_score' => 5]);
        $question = $practice->questions()->sole();
        $this->actingAs(User::factory()->create());
        $this->post(route('practices.start', $practice->slug))->assertRedirect();
        $attempt = ExamAttempt::sole();
        $expiresAt = $attempt->expires_at;
        $this->assertSame('practice', $attempt->kind);
        $this->assertSame(1800, (int) $attempt->started_at->diffInSeconds($expiresAt));
        $this->assertSame(route('quizzes.index'), $attempt->clientConfig()['exitUrl']);
        $this->get($attempt->attemptUrl())->assertSee('id="timerWrap"', false)
            ->assertDontSee('"answer":', false)->assertDontSee('"optionScores":', false)
            ->assertDontSee('"explainHtml":', false)->assertDontSee('Passing grade tryout');
        $this->patchJson(route('exam-attempts.save', $attempt), ['answers' => [(string) $question->id => 0], 'flags' => [(string) $question->id => true], 'current' => 0])->assertOk();
        $this->travel(2)->minutes();
        $this->post(route('practices.start', $practice->slug))->assertRedirect($attempt->attemptUrl());
        $this->assertDatabaseCount('exam_attempts', 1);
        $this->assertTrue($attempt->fresh()->expires_at->equalTo($expiresAt));
        $this->postJson(route('exam-attempts.submit', $attempt), ['score' => 999])->assertJsonPath('resultUrl', $attempt->resultUrl());
        $attempt->refresh();
        $this->assertSame(5, $attempt->score);
        $this->assertSame(5, $attempt->max_score);
        $this->assertTrue($attempt->hasPassed());
        $this->assertFalse($attempt->canPrintCertificate());
        $this->get($attempt->resultUrl())->assertSee('Hasil Latihan Soal Kamu')->assertSee('Kunci Jawaban')
            ->assertDontSee('Cetak Sertifikat')->assertDontSee('Pembahasan latihan khusus');
        $this->get($attempt->attemptUrl())->assertRedirect($attempt->resultUrl());
        $this->post(route('practices.start', $practice->slug))->assertRedirect();
        $this->assertDatabaseCount('exam_attempts', 2);
    }

    public function test_expired_practice_submits_saved_answers_automatically(): void
    {
        $this->freezeTime();
        $practice = $this->practice();
        $user = User::factory()->create();
        $attempt = ExamAttempt::resumeOrStart($user, $practice);
        $this->travel(31)->minutes();

        $this->actingAs($user)->get($attempt->attemptUrl())->assertRedirect($attempt->resultUrl());

        $this->assertDatabaseHas('exam_attempts', ['id' => $attempt->id, 'status' => 'completed', 'auto_submitted' => true, 'score' => 0, 'blank_count' => 1]);
    }

    public static function unavailablePackages(): array
    {
        return ['unrelated' => ['unrelated'], 'expired' => ['expired'], 'revoked' => ['revoked']];
    }

    #[DataProvider('unavailablePackages')]
    public function test_premium_practice_rejects_packages_without_current_access(string $state): void
    {
        $this->freezeTime();
        $practice = $this->practice(['access_level' => 'premium']);
        $user = User::factory()->create();
        $grant = UserPackage::factory()->for($user)->create(match ($state) {
            'expired' => ['expires_at' => now()->subMinute()],
            'revoked' => ['revoked_at' => now()],
            default => [],
        });
        if ($state !== 'unrelated') {
            $grant->exams()->attach($practice);
        }

        $this->actingAs($user)->post(route('practices.start', $practice->slug))->assertForbidden();

        $this->assertDatabaseCount('exam_attempts', 0);
    }

    public function test_premium_practice_works_with_exhausted_tryout_quota_and_unlocks_explanations(): void
    {
        $this->freezeTime();
        $practice = $this->practice(['access_level' => 'premium']);
        $user = User::factory()->create();
        $grant = UserPackage::factory()->for($user)->create(['tryout_quota' => 1, 'tryouts_used' => 1]);
        $grant->exams()->attach($practice);
        $this->actingAs($user)->get(route('quizzes.index'))->assertSee('Mulai latihan soal')->assertDontSee('Kuota tryout habis');
        $this->post(route('practices.start', $practice->slug))->assertRedirect();
        $attempt = ExamAttempt::sole();
        $this->assertSame($grant->id, $attempt->user_package_id);
        $this->assertSame(1, $grant->fresh()->tryouts_used);
        $this->postJson(route('exam-attempts.submit', $attempt))->assertOk();
        $this->get($attempt->resultUrl())->assertSee('Pembahasan latihan khusus');
    }

    public function test_history_statistics_and_dashboard_count_practice_separately(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        $practice = $this->practice();
        $attempt = ExamAttempt::resumeOrStart($user, $practice);
        $attempt->complete();
        $quiz = Exam::factory()->create();
        $quiz->questions()->attach(Question::factory()->published()->create());
        ExamAttempt::resumeOrStart($user, $quiz)->complete();
        $this->actingAs($user);

        $this->get(route('history.index', ['kind' => 'practice']))
            ->assertSee($practice->title)->assertDontSee($quiz->title)
            ->assertViewHas('summary', fn (array $summary): bool => $summary['practiceCount'] === 1 && $summary['quizCount'] === 1);
        $this->get(route('statistics.index', ['kind' => 'practice']))->assertViewHas('kind', 'practice')
            ->assertViewHas('summary', fn (array $summary): bool => $summary['count'] === 1 && $summary['latest']->id === $attempt->id);
        $this->get(route('dashboard'))->assertSee('Latihan Soal Dikerjakan')
            ->assertViewHas('summary', fn (array $summary): bool => $summary['practiceCount'] === 1);
    }

    public function test_practice_routes_enforce_authentication_ownership_and_kind(): void
    {
        $practice = $this->practice();
        $this->get(route('quizzes.index'))->assertRedirect(route('login'));
        $this->post(route('practices.start', $practice->slug))->assertRedirect(route('login'));
        $this->get(route('admin.practices.index'))->assertRedirect(route('login'));
        $owner = User::factory()->create();
        $attempt = ExamAttempt::resumeOrStart($owner, $practice);
        $this->actingAs(User::factory()->create());
        $this->get(route('admin.practices.index'))->assertForbidden();
        $this->get($attempt->attemptUrl())->assertNotFound();
        $this->patchJson(route('exam-attempts.save', $attempt), ['answers' => [], 'flags' => [], 'current' => 0])->assertNotFound();
        $this->postJson(route('exam-attempts.submit', $attempt))->assertNotFound();
        $attempt->complete();
        $this->get($attempt->resultUrl())->assertNotFound();
        $this->actingAs($owner)->get(route('tryouts.attempt', [$practice->slug, 'attempt' => $attempt->id]))->assertNotFound();
        $this->post(route('quizzes.start', $practice->slug))->assertNotFound();
        $this->post(route('practices.start', Exam::factory()->tryout()->create()->slug))->assertNotFound();
        $this->assertDatabaseCount('exam_attempts', 1);
    }

    public function test_drafts_and_insufficient_published_questions_cannot_start_practice(): void
    {
        $draft = $this->practice(['status' => 'draft']);
        $empty = Exam::factory()->practice()->create();
        $this->actingAs(User::factory()->create());

        $this->post(route('practices.start', $draft->slug))->assertNotFound();
        $this->post(route('practices.start', $empty->slug))->assertSessionHasErrors('exam');

        $this->assertDatabaseCount('exam_attempts', 0);
    }

    public function test_admin_question_filters_and_package_selection_include_standalone_practice(): void
    {
        $practice = $this->practice(['access_level' => 'premium']);
        $question = $practice->questions()->sole();
        $topicQuestion = Question::factory()->published()->create();
        $quiz = Exam::factory()->create();
        $quiz->questions()->attach($topicQuestion);
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('admin.questions.index', ['usage' => 'practice']))
            ->assertSee($question->title)->assertDontSee($topicQuestion->title)
            ->assertSee(route('admin.practices.edit', $practice));
        $this->get(route('admin.questions.edit', $question))->assertSee($practice->title)->assertSee('Latihan Soal');
        $this->post(route('admin.packages.store'), ['name' => 'Paket Latihan Mandiri', 'price' => 10000,
            'tryout_quota' => 0, 'is_active' => true, 'position' => 0, 'exam_ids' => [$practice->id]])
            ->assertSessionHasNoErrors();

        $this->assertSame([$practice->id], Package::sole()->exams()->pluck('exams.id')->all());
    }

    /** @param array<string, mixed> $attributes */
    private function practice(array $attributes = []): Exam
    {
        $practice = Exam::factory()->practice()->create(array_replace(['random_questions' => false, 'random_options' => false], $attributes));
        $practice->questions()->attach(Question::factory()->published()->create(['correct_option' => 0, 'explanation' => '<p>Pembahasan latihan khusus</p>']));

        return $practice;
    }

    /** @return array<string, mixed> */
    private function payload(Question $question): array
    {
        return ['title' => 'Latihan Soal Mandiri', 'slug' => 'latihan-soal-mandiri', 'kind' => 'practice', 'status' => 'published',
            'question_count' => 1, 'duration_minutes' => 30, 'points_per_correct' => 5, 'penalty_wrong' => 0,
            'random_questions' => true, 'random_options' => true, 'question_ids' => [$question->id]];
    }
}
