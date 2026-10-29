<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\QuestionStatus;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\Section;
use App\Models\User;
use App\Models\UserPackage;
use Database\Seeders\ExamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DatabaseExamTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_replace(['title' => 'Latihan Numerik', 'slug' => 'latihan-numerik', 'kind' => 'quiz',
            'description' => 'Latihan dari bank soal', 'question_count' => 1, 'duration_minutes' => 0,
            'points_per_correct' => 5, 'penalty_wrong' => 0, 'passing_score' => 5,
            'random_questions' => true, 'random_options' => true, 'status' => 'published',
            'question_ids' => [Question::factory()->published()->create()->id]], $overrides);
    }

    private function exam(int $count = 3, array $overrides = []): Exam
    {
        $exam = Exam::factory()->create(array_replace(['question_count' => $count,
            'random_questions' => false, 'random_options' => false], $overrides));
        $exam->questions()->attach(Question::factory()->published()->count($count)->create());

        return $exam;
    }

    public function test_admin_manages_sessions_and_participants_cannot_manage_them(): void
    {
        $admin = User::factory()->admin()->create();
        $data = $this->payload();
        $this->actingAs($admin)->post(route('admin.materials.quizzes.store'), $data)->assertSessionHasNoErrors();
        $exam = Exam::sole();
        $this->assertSame($data['question_ids'], $exam->questions()->pluck('questions.id')->all());
        $this->assertSame($admin->id, $exam->created_by);
        $this->get(route('admin.materials.quizzes.index'))->assertOk()->assertSee('Latihan Numerik');
        $this->get(route('admin.materials.quizzes.edit', $exam))->assertOk()->assertSee('Jumlah soal');
        $this->put(route('admin.materials.quizzes.update', $exam), array_replace($data, ['title' => 'Revisi Latihan']))->assertSessionHasNoErrors();
        $this->assertSame('Revisi Latihan', $exam->fresh()->title);
        $this->actingAs(User::factory()->create())->post(route('admin.materials.quizzes.store'), $data)->assertForbidden();
        $this->delete(route('admin.materials.quizzes.destroy', $exam))->assertForbidden();
        $this->actingAs($admin)->delete(route('admin.materials.quizzes.destroy', $exam))->assertRedirect();
        $this->assertSoftDeleted($exam);
    }

    public function test_publication_requires_enough_available_questions_and_valid_scoring(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $draft = Question::factory()->create();
        $data = $this->payload(['question_ids' => [$draft->id]]);
        $this->post(route('admin.materials.quizzes.store'), $data)->assertSessionHasErrors('question_ids');
        $this->post(route('admin.materials.quizzes.store'), array_replace($data, ['status' => 'draft']))->assertSessionHasNoErrors();
        $this->postJson(route('admin.exams.store'), $this->payload(['slug' => 'invalid-tryout', 'kind' => 'tryout',
            'duration_minutes' => 0, 'passing_score' => 6]))->assertUnprocessable()->assertJsonValidationErrors(['duration_minutes', 'passing_score']);
        $this->postJson(route('admin.materials.quizzes.store'), $this->payload(['slug' => 'invalid-pool', 'question_count' => 2]))
            ->assertUnprocessable()->assertJsonValidationErrors('question_ids');
        $this->postJson(route('admin.materials.quizzes.store'), $this->payload(['slug' => 'invalid-options', 'question_ids' => [['bad' => 1]]]))
            ->assertUnprocessable()->assertJsonValidationErrors('question_ids.0');
    }

    public function test_unpublished_future_inactive_or_deleted_questions_cannot_fill_the_pool(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $questions = collect([
            Question::factory()->create(),
            Question::factory()->published()->create(['published_at' => now()->addDay()]),
            Question::factory()->published()->for(Section::factory()->inactive())->create(),
        ]);
        foreach ($questions as $question) {
            $this->post(route('admin.materials.quizzes.store'), $this->payload(['question_ids' => [$question->id]]))
                ->assertSessionHasErrors('question_ids');
        }
        $deleted = Question::factory()->published()->create();
        $deleted->delete();
        $this->postJson(route('admin.materials.quizzes.store'), $this->payload(['question_ids' => [$deleted->id]]))
            ->assertUnprocessable()->assertJsonValidationErrors('question_ids.0');
    }

    public function test_kind_and_slug_remain_stable_after_creation(): void
    {
        $exam = $this->exam(1);
        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.materials.quizzes.update', $exam), $this->payload(['slug' => 'changed-address']))
            ->assertSessionHasErrors('slug');
        $this->assertNotSame('changed-address', $exam->fresh()->slug);
    }

    public function test_catalog_uses_published_database_sessions_and_starting_is_a_post_action(): void
    {
        $exam = $this->exam(1, ['title' => 'Sesi Terbit Nyata']);
        Exam::factory()->create(['title' => 'Draft Rahasia', 'status' => 'draft']);
        Exam::factory()->tryout()->create(['title' => 'Tryout Nyata']);
        $this->actingAs(User::factory()->create())->get(route('quizzes.topics'))->assertOk()
            ->assertSee('Sesi Terbit Nyata')->assertDontSee('Draft Rahasia')->assertDontSee('Tryout Nyata');
        $this->get(route('quizzes.attempt', $exam->slug))->assertNotFound();
        $this->post(route('quizzes.start', 'latihan-1'))->assertNotFound();
        $this->assertDatabaseCount('exam_attempts', 0);
        $this->post(route('quizzes.start', $exam->slug))->assertRedirect();
        $attempt = ExamAttempt::sole();
        $this->get($attempt->attemptUrl())->assertOk()->assertDontSee('"answer":', false)->assertDontSee('"explainHtml":', false)
            ->assertViewHas('clientConfig', fn (array $config): bool => $config['showTimer'] === false && $config['expiresAt'] === null);
        $this->post(route('quizzes.start', $exam->slug))->assertRedirect($attempt->attemptUrl());
        $this->assertDatabaseCount('exam_attempts', 1);
    }

    public function test_session_access_requires_an_active_verified_account_and_available_pool(): void
    {
        $exam = $this->exam(1);
        $this->post(route('quizzes.start', $exam->slug))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->unverified()->create())->post(route('quizzes.start', $exam->slug))
            ->assertRedirect(route('verification.notice'));
        $this->actingAs(User::factory()->inactive()->create())->post(route('quizzes.start', $exam->slug))->assertRedirect(route('login'));
        $exam->questions->first()->update(['status' => QuestionStatus::Draft]);
        $this->actingAs(User::factory()->create())->post(route('quizzes.start', $exam->slug))->assertSessionHasErrors('exam');
        $this->assertDatabaseCount('exam_attempts', 0);
    }

    public function test_quiz_and_tryout_use_configured_points_with_server_owned_scoring(): void
    {
        $user = User::factory()->create();
        foreach (['quiz' => 5, 'tryout' => 5] as $kind => $expected) {
            $exam = $this->exam(3, ['kind' => $kind, 'duration_minutes' => $kind === 'tryout' ? 1 : 0, 'passing_score' => 5]);
            $attempt = ExamAttempt::resumeOrStart($user, $exam);
            $ids = array_column($attempt->question_snapshot['questions'], 'id');
            $this->actingAs($user)->patchJson(route('exam-attempts.save', $attempt),
                ['answers' => [$ids[0] => 1, $ids[1] => 0], 'flags' => [], 'current' => 1])->assertOk();
            $this->postJson(route('exam-attempts.submit', $attempt), ['score' => 99999, 'answers' => [$ids[2] => 1]])->assertOk();
            $attempt->refresh();
            $this->assertSame($expected, $attempt->score);
            $this->assertSame(15, $attempt->max_score);
            $this->assertSame([1, 1, 1], [$attempt->correct_count, $attempt->wrong_count, $attempt->blank_count]);
            $this->get($attempt->resultUrl())->assertOk()->assertSee('Kunci Jawaban')->assertSee('Jawaban benar');
        }
    }

    public function test_configured_penalty_cannot_make_the_score_negative(): void
    {
        $user = User::factory()->create();
        $attempt = ExamAttempt::resumeOrStart($user, $this->exam(2, ['penalty_wrong' => 10]));
        $questions = $attempt->question_snapshot['questions'];
        $attempt->update(['answers' => [$questions[0]['id'] => 1, $questions[1]['id'] => 0]]);
        $attempt->complete();
        $this->assertSame(0, $attempt->score);
    }

    public function test_quiz_accepts_and_awards_more_than_one_hundred_points_and_validates_its_actual_maximum(): void
    {
        $questions = Question::factory()->published()->count(3)->create();
        $data = $this->payload(['question_ids' => $questions->modelKeys(), 'question_count' => 3,
            'points_per_correct' => 50, 'passing_score' => 120, 'random_questions' => false, 'random_options' => false]);

        $this->actingAs(User::factory()->admin()->create())->post(route('admin.materials.quizzes.store'), $data)
            ->assertSessionHasNoErrors();

        $exam = Exam::sole();
        $this->assertSame(150, $exam->maximumScore());
        $this->put(route('admin.materials.quizzes.update', $exam), array_replace($data, ['passing_score' => 151]))
            ->assertSessionHasErrors(['passing_score' => 'Nilai ambang tidak boleh melebihi skor maksimal sesi.']);
        $this->assertSame(120, $exam->fresh()->passing_score);

        $user = User::factory()->create();
        UserPackage::factory()->for($user)->create(['tier' => 'premium', 'tryout_quota' => null]);
        $attempt = ExamAttempt::resumeOrStart($user, $exam);
        $this->actingAs($user)->patchJson(route('exam-attempts.save', $attempt), [
            'answers' => array_fill_keys($questions->modelKeys(), 1), 'flags' => [], 'current' => 0,
        ])->assertOk();
        $this->postJson(route('exam-attempts.submit', $attempt))->assertOk();

        $attempt->refresh();
        $this->assertSame([150, 150, 120], [$attempt->score, $attempt->max_score, $attempt->passing_score]);
        $this->get(route('quizzes.topics'))->assertSee('150 / 150');
        $this->get($attempt->resultUrl())->assertSee('dari 150')->assertSee('Di atas ambang');
        $this->get(route('statistics.index', ['kind' => 'quiz']))
            ->assertViewHas('trendChart', fn (array $chart): bool => $chart['values'] === [150]);
    }

    public function test_partial_saves_merge_two_tabs_and_explicit_clear_does_not_remove_other_answers(): void
    {
        $user = User::factory()->create();
        $attempt = ExamAttempt::resumeOrStart($user, $this->exam());
        $ids = array_column($attempt->question_snapshot['questions'], 'id');
        $this->actingAs($user);
        $this->patchJson(route('exam-attempts.save', $attempt), ['answers' => [$ids[0] => 1], 'flags' => [$ids[0] => true], 'current' => 0])->assertOk();
        $this->patchJson(route('exam-attempts.save', $attempt), ['answers' => [$ids[1] => 2], 'flags' => [$ids[1] => true], 'current' => 1])->assertOk();
        $this->patchJson(route('exam-attempts.save', $attempt), ['answers' => [$ids[0] => null], 'flags' => [$ids[0] => false], 'current' => 2])->assertOk();
        $attempt->refresh();
        $this->assertSame([(string) $ids[1] => 2], $attempt->answers);
        $this->assertSame([(string) $ids[1] => true], $attempt->flags);
        $this->assertSame(2, $attempt->current_index);
        $this->get($attempt->attemptUrl())->assertViewHas('clientConfig', fn (array $config): bool => $config['state']['current'] === 2 && $config['state']['answers'] === $attempt->answers);
    }

    public function test_randomized_snapshot_preserves_options_and_correct_key_across_reload_and_admin_changes(): void
    {
        $user = User::factory()->create();
        $exam = $this->exam(3, ['random_questions' => true, 'random_options' => true]);
        $attempt = ExamAttempt::resumeOrStart($user, $exam);
        $snapshot = $attempt->question_snapshot;
        foreach ($snapshot['questions'] as $question) {
            $this->assertEqualsCanonicalizing(['18', '20', '22', '24', '26'], $question['options']);
            $this->assertSame('20', $question['options'][$question['answer']]);
        }
        $source = $exam->questions->first();
        $source->update(['body' => '<p>Soal sudah direvisi</p>', 'options' => ['Baru', 'Berbeda'], 'correct_option' => 0, 'explanation' => '<p>Penjelasan baru</p>']);
        $exam->update(['points_per_correct' => 20, 'duration_minutes' => 5, 'random_options' => false]);
        $this->assertSame($snapshot, ExamAttempt::resumeOrStart($user, $exam)->question_snapshot);
        $this->actingAs($user)->get($attempt->attemptUrl())->assertOk()->assertDontSee('Soal sudah direvisi');
        $attempt->complete();
        $next = ExamAttempt::resumeOrStart($user, $exam);
        $this->assertNotSame($attempt->id, $next->id);
        $this->assertSame(20, $next->question_snapshot['pointsPerCorrect']);
        $this->assertSame(300, $next->question_snapshot['durationSeconds']);
        $this->assertContains('Soal sudah direvisi', array_column($next->question_snapshot['questions'], 'stem'));
    }

    public function test_media_is_private_and_survives_question_replacement_and_archiving(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('questions/videos/original.mp4', 'video fixture');
        Storage::disk('local')->put('questions/images/original.png', 'image fixture');
        $exam = $this->exam(1);
        $question = $exam->questions->first();
        $question->update(['video_path' => 'questions/videos/original.mp4', 'image_path' => 'questions/images/original.png']);
        $owner = User::factory()->create();
        $attempt = ExamAttempt::resumeOrStart($owner, $exam);
        $config = $attempt->clientConfig()['questions'][0];
        $this->assertArrayNotHasKey('video_path', $config);
        $this->assertArrayNotHasKey('answer', $config);
        $this->assertArrayNotHasKey('mediaPaths', $attempt->clientConfig());
        $this->actingAs(User::factory()->create())->get($config['videoUrl'])->assertNotFound();
        $this->actingAs($owner)->get($config['videoUrl'])->assertOk()->assertHeader('Content-Type', 'video/mp4');
        $this->get($config['imageUrl'])->assertOk();
        $this->actingAs(User::factory()->admin()->create())->put(route('admin.questions.update', $question),
            ['title' => $question->title, 'section_id' => $question->section_id, 'body' => '<p>Revisi</p>',
                'options' => $question->options, 'correct_option' => 1, 'explanation' => '<p>Penjelasan</p>',
                'difficulty' => 'easy', 'status' => 'published', 'remove_video' => true, 'remove_image' => true])->assertSessionHasNoErrors();
        Storage::disk('local')->assertExists('questions/videos/original.mp4');
        Storage::disk('local')->assertExists('questions/images/original.png');
        $question->delete();
        $exam->delete();
        $attempt->complete();
        $this->actingAs($owner)->get($attempt->resultUrl())->assertOk()->assertSee('<video', false);
        $this->get($config['videoUrl'])->assertOk();
        $this->get($attempt->attemptUrl())->assertRedirect($attempt->resultUrl());
        $this->post(route('quizzes.start', $exam->slug))->assertNotFound();
    }

    public function test_deadline_rejects_last_second_changes_and_scheduled_completion_clamps_duration(): void
    {
        $this->travelTo(now()->startOfSecond());
        $user = User::factory()->create();
        $attempt = ExamAttempt::resumeOrStart($user, $this->exam(1, ['kind' => 'tryout', 'duration_minutes' => 1]));
        $id = $attempt->question_snapshot['questions'][0]['id'];
        $this->actingAs($user)->patchJson(route('exam-attempts.save', $attempt),
            ['answers' => [$id => 1], 'flags' => [], 'current' => 0])->assertOk();
        $this->travel(60)->seconds();
        $this->patchJson(route('exam-attempts.save', $attempt), ['answers' => [$id => 0], 'flags' => [], 'current' => 0])->assertConflict();
        $attempt->refresh();
        $this->assertSame(5, $attempt->score);
        $this->assertSame(60, $attempt->duration_seconds);
        $this->assertTrue($attempt->auto_submitted);
        $this->assertTrue($attempt->submitted_at->equalTo($attempt->expires_at));
        $second = ExamAttempt::resumeOrStart($user, $this->exam(1, ['kind' => 'tryout', 'duration_minutes' => 1]));
        $this->travel(10)->minutes();
        $this->artisan('exams:finalize-expired')->assertSuccessful();
        $second->refresh();
        $this->assertSame(ExamAttempt::COMPLETED, $second->status);
        $this->assertSame(60, $second->duration_seconds);
        $this->artisan('exams:finalize-expired')->assertSuccessful();
        $this->postJson(route('exam-attempts.submit', $attempt))->assertOk();
        $this->assertSame(5, $attempt->fresh()->score);
    }

    public function test_history_contains_owned_active_and_completed_sessions_with_status_filter(): void
    {
        $user = User::factory()->create();
        $exam = $this->exam(1, ['title' => 'Sesi Pribadi']);
        $done = ExamAttempt::resumeOrStart($user, $exam);
        $done->complete();
        $active = ExamAttempt::resumeOrStart($user, $exam);
        ExamAttempt::resumeOrStart(User::factory()->create(), $this->exam(1, ['title' => 'Sesi Orang Lain']));
        $this->actingAs($user)->get(route('history.index'))->assertOk()->assertSee('Lanjutkan Sesi')->assertSee('Lihat Hasil')
            ->assertDontSee('Sesi Orang Lain')->assertViewHas('attempts', fn ($items): bool => $items->total() === 2);
        $this->get(route('history.index', ['status' => 'in_progress']))->assertSee($active->attemptUrl(), false)->assertDontSee('Lihat Hasil');
        $this->get(route('history.index', ['status' => 'completed']))->assertSee($done->resultUrl(), false)->assertDontSee('Lanjutkan Sesi');
        $exam->update(['status' => QuestionStatus::Draft]);
        $this->get($active->attemptUrl())->assertOk();
        $done->refresh();
        $this->get($done->resultUrl())->assertOk()->assertDontSee('Kerjakan Lagi');
    }

    public function test_exam_seeding_is_idempotent_and_preserves_admin_changes(): void
    {
        Question::factory()->published()->create(['title' => 'Contoh: Soal']);
        $this->seed(ExamSeeder::class);
        $exam = Exam::where('kind', 'quiz')->sole();
        $exam->update(['title' => 'Judul Admin', 'duration_minutes' => 4]);
        $this->seed(ExamSeeder::class);
        $this->assertDatabaseCount('exams', 2);
        $this->assertSame('Judul Admin', $exam->fresh()->title);
        $this->assertSame(4, $exam->fresh()->duration_minutes);
        $exam->delete();
        $this->seed(ExamSeeder::class);
        $this->assertSame(2, Exam::withTrashed()->count());
        $this->assertSoftDeleted($exam);
    }
}
