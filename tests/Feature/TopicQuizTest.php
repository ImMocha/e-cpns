<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MaterialStatus;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Material;
use App\Models\MaterialProgress;
use App\Models\Question;
use App\Models\Section;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TopicQuizTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_associates_quiz_with_topic_and_manages_completion_requirement_and_questions(): void
    {
        $topic = Topic::factory()->create();
        $question = Question::factory()->published()->create(['section_id' => $topic->section_id]);
        $data = $this->payload($topic, $question);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.materials.quizzes.create', ['topic_id' => $topic->id]))
            ->assertOk()->assertSee($topic->name)->assertSee('Kapan saja')->assertSee('Setelah semua materi topik ditandai selesai')
            ->assertViewHas('exam', fn (Exam $exam): bool => (int) $exam->topic_id === $topic->id);
        $this->post(route('admin.materials.quizzes.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
        $quiz = Exam::sole();
        $this->assertSame($topic->id, $quiz->topic_id);
        $this->assertFalse($quiz->requires_topic_completion);
        $replacement = Question::factory()->published()->create(['section_id' => $topic->section_id]);
        $this->put(route('admin.materials.quizzes.update', $quiz), array_replace($data, [
            'requires_topic_completion' => true, 'question_ids' => [$replacement->id],
        ]))->assertSessionHasNoErrors()->assertRedirect();

        $this->assertTrue($quiz->fresh()->requires_topic_completion);
        $this->assertSame([$replacement->id], $quiz->questions()->pluck('questions.id')->all());
        $this->actingAs(User::factory()->create())->put(route('admin.materials.quizzes.update', $quiz), $data)->assertForbidden();
        $this->assertTrue($quiz->fresh()->requires_topic_completion);
    }

    public function test_topic_can_be_changed_or_removed_without_changing_standalone_quizzes(): void
    {
        $first = Topic::factory()->create();
        $second = Topic::factory()->create();
        $quiz = $this->quiz($first);
        $data = $this->payload($first, $quiz->questions->first());
        $data['slug'] = $quiz->slug;
        $this->actingAs(User::factory()->admin()->create())->put(route('admin.materials.quizzes.update', $quiz),
            array_replace($data, ['topic_id' => $second->id]))->assertSessionHasNoErrors();
        $this->assertSame($second->id, $quiz->fresh()->topic_id);
        $this->put(route('admin.materials.quizzes.update', $quiz), array_replace($data, ['topic_id' => null]))->assertSessionHasNoErrors();
        $this->assertNull($quiz->fresh()->topic_id);
    }

    #[DataProvider('invalidSettings')]
    public function test_invalid_topic_settings_are_rejected(array $settings, string $field): void
    {
        $topic = Topic::factory()->create();
        $question = Question::factory()->published()->create();
        $this->actingAs(User::factory()->admin()->create())->post(route('admin.materials.quizzes.store'),
            array_replace($this->payload($topic, $question), $settings))->assertSessionHasErrors($field);

        $this->assertDatabaseCount('exams', 0);
    }

    /** @return array<string, array{array<string, mixed>, string}> */
    public static function invalidSettings(): array
    {
        return [
            'unknown topic' => [['topic_id' => 999999], 'topic_id'],
            'invalid topic type' => [['topic_id' => ['invalid']], 'topic_id'],
            'tryout cannot belong to topic' => [['kind' => 'tryout', 'duration_minutes' => 30], 'topic_id'],
            'completion needs topic' => [['topic_id' => null, 'requires_topic_completion' => true], 'requires_topic_completion'],
            'completion is boolean' => [['requires_topic_completion' => 'sometimes'], 'requires_topic_completion'],
        ];
    }

    public function test_deleted_topic_cannot_be_assigned(): void
    {
        $topic = Topic::factory()->create();
        $question = Question::factory()->published()->create();
        $topic->delete();
        $this->actingAs(User::factory()->admin()->create())->post(route('admin.materials.quizzes.store'), $this->payload($topic, $question))
            ->assertSessionHasErrors('topic_id');
        $this->assertDatabaseCount('exams', 0);
    }

    public function test_quizzes_appear_after_last_material_in_their_own_topic_and_on_reader_page(): void
    {
        $first = Topic::factory()->create(['name' => 'Topik Pertama', 'position' => 0]);
        $second = Topic::factory()->create(['section_id' => $first->section_id, 'name' => 'Topik Kedua', 'position' => 1]);
        $material = $this->material($first, ['title' => 'Materi Pertama', 'position' => 0]);
        $this->material($first, ['title' => 'Materi Terakhir', 'position' => 1]);
        $this->material($second, ['title' => 'Materi Kedua']);
        $this->quiz($first, ['title' => 'Latihan Pertama']);
        $this->quiz($second, ['title' => 'Latihan Kedua']);
        $this->quiz($first, ['title' => 'Latihan Draft', 'status' => 'draft']);
        Exam::factory()->create(['title' => 'Latihan Umum']);

        $this->actingAs(User::factory()->create())->get(route('materials.index'))->assertOk()
            ->assertSeeInOrder(['Topik Pertama', 'Materi Pertama', 'Materi Terakhir', 'Latihan Pertama', 'Topik Kedua', 'Materi Kedua', 'Latihan Kedua'])
            ->assertDontSee('Latihan Draft')->assertDontSee('Latihan Umum');
        $this->get(route('materials.show', $material))->assertOk()->assertSee('Latihan Pertama')->assertDontSee('Latihan Kedua');
    }

    public function test_topic_quiz_section_is_hidden_when_no_published_quiz_exists(): void
    {
        $topic = Topic::factory()->create();
        $material = $this->material($topic);
        $quiz = $this->quiz($topic, ['status' => 'draft']);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('materials.index'))->assertOk()->assertDontSee('Latihan Topik')
            ->assertDontSee('Belum ada soal latihan');
        $this->get(route('materials.show', $material))->assertOk()->assertDontSee('Latihan Topik');

        $quiz->update(['status' => 'published']);

        $this->get(route('materials.index'))->assertSee('Latihan Topik')->assertSee($quiz->title);
        $this->get(route('materials.show', $material))->assertSee('Latihan Topik')->assertSee($quiz->title);
    }

    public function test_unrestricted_quiz_can_start_without_finishing_materials(): void
    {
        $topic = Topic::factory()->create();
        $this->material($topic);
        $quiz = $this->quiz($topic);
        $this->actingAs(User::factory()->create())->post(route('quizzes.start', $quiz->slug))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('exam_attempts', ['bank_slug' => $quiz->slug, 'status' => ExamAttempt::IN_PROGRESS]);
    }

    public function test_gated_quiz_requires_every_published_material_and_the_users_own_progress_even_with_filters(): void
    {
        $topic = Topic::factory()->create();
        $first = $this->material($topic, ['title' => 'Pengantar Dasar']);
        $second = $this->material($topic, ['title' => 'Pendalaman Materi']);
        $this->material($topic, ['status' => MaterialStatus::Draft]);
        $deleted = $this->material($topic);
        $deleted->delete();
        $quiz = $this->quiz($topic, ['requires_topic_completion' => true]);
        $user = User::factory()->create();
        MaterialProgress::factory()->for($first)->create(['completed_at' => now()]);
        MaterialProgress::factory()->for($second)->create(['completed_at' => now()]);
        $this->actingAs($user)->post(route('quizzes.start', $quiz->slug))
            ->assertSessionHasErrors(['exam' => 'Selesaikan semua materi pada topik ini sebelum memulai latihan.']);
        $this->assertDatabaseCount('exam_attempts', 0);
        $this->post(route('materials.progress.store', $first))->assertRedirect();
        $this->get(route('materials.index', ['q' => 'Pengantar Dasar']))->assertOk()
            ->assertSee('Selesaikan materi topik dahulu')->assertSee('1/2')->assertDontSee('Pendalaman Materi')
            ->assertSee('Selesaikan semua materi untuk membuka latihan.')->assertDontSee('Mulai latihan')
            ->assertDontSee('Pratinjau sebagai admin');
        $this->get(route('quizzes.index'))->assertSee('Selesaikan materi topik dahulu');
        $this->post(route('quizzes.start', $quiz->slug))->assertSessionHasErrors('exam');
        $this->post(route('materials.progress.store', $second))->assertRedirect();
        $this->get(route('materials.show', $second))->assertSee('2/2')->assertSee('Mulai latihan')
            ->assertSee('Semua materi selesai. Latihan terbuka.');
        $this->post(route('quizzes.start', $quiz->slug))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseCount('exam_attempts', 1);
    }

    public function test_gated_quiz_in_empty_topic_stays_locked_but_admin_can_preview(): void
    {
        $topic = Topic::factory()->create();
        $quiz = $this->quiz($topic, ['requires_topic_completion' => true]);
        $this->actingAs(User::factory()->create())->post(route('quizzes.start', $quiz->slug))->assertSessionHasErrors('exam');
        $this->assertDatabaseCount('exam_attempts', 0);
        $this->actingAs(User::factory()->admin()->create())->post(route('quizzes.start', $quiz->slug))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('exam_attempts', 1);
    }

    public function test_incomplete_topic_clearly_labels_admin_preview_in_materials_and_quiz_catalog(): void
    {
        $topic = Topic::factory()->create();
        $material = $this->material($topic);
        $quiz = $this->quiz($topic, ['requires_topic_completion' => true]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('materials.index'))->assertSee('0/1')
            ->assertSee('Pratinjau admin: bisa dibuka tanpa menyelesaikan materi.')
            ->assertSee('Pratinjau sebagai admin')->assertDontSee('Mulai latihan')
            ->assertDontSee('Semua materi selesai. Latihan terbuka.');
        $this->get(route('materials.show', $material))->assertSee('Pratinjau sebagai admin');
        $this->get(route('quizzes.index'))->assertSee('Pratinjau sebagai admin')
            ->assertSee('Pratinjau admin: bisa dibuka tanpa menyelesaikan materi.');

        $this->post(route('quizzes.start', $quiz->slug))->assertRedirect()->assertSessionHasNoErrors();
        $this->get(route('materials.index'))->assertSee('Lanjutkan pratinjau');
    }

    public function test_premium_topic_quiz_still_requires_covering_package_and_does_not_use_tryout_quota(): void
    {
        $topic = Topic::factory()->create();
        $quiz = $this->quiz($topic, ['access_level' => 'premium']);
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('quizzes.start', $quiz->slug))->assertForbidden();
        $this->assertDatabaseCount('exam_attempts', 0);
        $grant = UserPackage::factory()->for($user)->create(['tryout_quota' => 0]);
        $grant->exams()->attach($quiz);
        $this->post(route('quizzes.start', $quiz->slug))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(0, $grant->fresh()->tryouts_used);
    }

    public function test_inactive_deleted_topic_or_inactive_section_hides_quizzes_and_blocks_direct_start(): void
    {
        $topic = Topic::factory()->create();
        $quiz = $this->quiz($topic, ['title' => 'Latihan Tersembunyi']);
        $this->actingAs(User::factory()->create());
        $topic->update(['is_active' => false]);
        $this->get(route('quizzes.index'))->assertDontSee($quiz->title);
        $this->post(route('quizzes.start', $quiz->slug))->assertNotFound();
        $topic->update(['is_active' => true]);
        $topic->section->update(['is_active' => false]);
        $this->post(route('quizzes.start', $quiz->slug))->assertNotFound();
        $topic->section->update(['is_active' => true]);
        $topic->delete();
        $this->get(route('quizzes.index'))->assertDontSee($quiz->title);
        $this->post(route('quizzes.start', $quiz->slug))->assertNotFound();
        $this->assertDatabaseCount('exam_attempts', 0);
    }

    public function test_existing_attempt_can_resume_after_requirement_changes_without_losing_history(): void
    {
        $topic = Topic::factory()->create();
        $quiz = $this->quiz($topic);
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('quizzes.start', $quiz->slug))->assertRedirect();
        $attempt = ExamAttempt::sole();
        $quiz->update(['requires_topic_completion' => true]);
        $this->get(route('quizzes.index'))->assertSee('Lanjutkan');
        $this->post(route('quizzes.start', $quiz->slug))->assertRedirect($attempt->attemptUrl());
        $this->assertDatabaseCount('exam_attempts', 1);
        $this->postJson(route('exam-attempts.submit', $attempt))->assertOk();
        $this->get(route('quizzes.index'))->assertSee('Hasil terakhir')->assertSee('Selesaikan materi topik dahulu');
        $this->get($attempt->resultUrl())->assertOk();
        $this->post(route('quizzes.start', $quiz->slug))->assertSessionHasErrors('exam');
    }

    public function test_admin_topic_shortcuts_filter_quizzes_and_topics_with_quizzes_cannot_be_deleted_or_moved(): void
    {
        $topic = Topic::factory()->create();
        $quiz = $this->quiz($topic, ['title' => 'Latihan Topik Pilihan']);
        Exam::factory()->create(['title' => 'Latihan Tidak Terkait']);
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.topics.index'))->assertSee('Kelola Latihan');
        $this->get(route('admin.materials.quizzes.index', ['kind' => 'quiz', 'topic_id' => $topic->id]))
            ->assertSee($quiz->title)->assertDontSee('Latihan Tidak Terkait');
        $this->delete(route('admin.topics.destroy', $topic))->assertSessionHas('error');
        $this->assertNotSoftDeleted($topic);
        $this->put(route('admin.topics.update', $topic), ['name' => $topic->name, 'section_id' => Section::factory()->create()->id,
            'is_active' => true, 'position' => 0])->assertSessionHasErrors('section_id');
        $this->assertSame($topic->section_id, $topic->fresh()->section_id);
        $quiz->delete();
        $this->delete(route('admin.topics.destroy', $topic))->assertSessionHas('error');
        $this->assertNotSoftDeleted($topic);
    }

    public function test_topic_quiz_title_and_description_are_escaped(): void
    {
        $topic = Topic::factory()->create();
        $this->material($topic);
        $quiz = $this->quiz($topic, ['title' => '<script>alert(1)</script>', 'description' => '<img src=x onerror=alert(2)>']);
        $this->actingAs(User::factory()->create())->get(route('materials.index'))
            ->assertSee($quiz->title)->assertSee($quiz->description)->assertDontSee($quiz->title, false)->assertDontSee($quiz->description, false);
    }

    /** @param array<string, mixed> $overrides */
    private function quiz(Topic $topic, array $overrides = []): Exam
    {
        $quiz = Exam::factory()->for($topic)->create($overrides);
        $quiz->questions()->attach(Question::factory()->published()->create(['section_id' => $topic->section_id]));

        return $quiz;
    }

    /** @param array<string, mixed> $overrides */
    private function material(Topic $topic, array $overrides = []): Material
    {
        return Material::factory()->video()->for($topic)->create($overrides + ['section_id' => $topic->section_id]);
    }

    /** @return array<string, mixed> */
    private function payload(Topic $topic, Question $question): array
    {
        return ['title' => 'Latihan Materi Topik', 'slug' => 'latihan-materi-topik', 'kind' => 'quiz',
            'question_count' => 1, 'duration_minutes' => 0, 'points_per_correct' => 5, 'penalty_wrong' => 0,
            'random_questions' => false, 'random_options' => false, 'status' => 'published',
            'topic_id' => $topic->id, 'requires_topic_completion' => false, 'question_ids' => [$question->id]];
    }
}
