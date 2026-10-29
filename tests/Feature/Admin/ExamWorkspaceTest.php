<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Exam;
use App\Models\Material;
use App\Models\Question;
use App\Models\Section;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_tryout_and_material_quiz_pages_have_separate_catalogs_and_creation_defaults(): void
    {
        $quiz = Exam::factory()->create(['title' => 'Latihan Materi Khusus']);
        $tryout = Exam::factory()->tryout()->create(['title' => 'Simulasi Tryout Khusus']);
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('admin.exams.index'))->assertSee($tryout->title)->assertDontSee($quiz->title)
            ->assertSee('Tambah Tryout')->assertDontSee('Quiz &amp; Tryout', false);
        $this->get(route('admin.materials.quizzes.index'))->assertSee($quiz->title)->assertDontSee($tryout->title)
            ->assertSee('Tambah Latihan')->assertSee('Latihan Soal umum');
        $this->get(route('admin.exams.create'))->assertViewHas('exam', fn (Exam $exam): bool => $exam->kind === 'tryout' && $exam->duration_minutes === 30)
            ->assertSee('Tambah Tryout')->assertDontSee('Topik latihan');
        $this->get(route('admin.materials.quizzes.create'))->assertViewHas('exam', fn (Exam $exam): bool => $exam->kind === 'quiz')
            ->assertSee('Tambah Latihan')->assertSee('Topik latihan');
    }

    public function test_legacy_quiz_links_redirect_to_material_workspace(): void
    {
        $topic = Topic::factory()->create();
        $quiz = Exam::factory()->for($topic)->create();
        $tryout = Exam::factory()->tryout()->create();
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('admin.exams.index', ['kind' => 'quiz', 'topic_id' => $topic->id]))
            ->assertRedirect(route('admin.materials.quizzes.index', ['topic_id' => $topic->id]));
        $this->get(route('admin.exams.create', ['topic_id' => $topic->id]))
            ->assertRedirect(route('admin.materials.quizzes.create', ['topic_id' => $topic->id]));
        $this->get(route('admin.exams.edit', $quiz))->assertRedirect(route('admin.materials.quizzes.edit', $quiz));
        $this->get(route('admin.materials.quizzes.edit', $tryout))->assertRedirect(route('admin.exams.edit', $tryout));
    }

    public function test_topic_workspace_shows_its_quizzes_and_scopes_the_quiz_filters(): void
    {
        $topic = Topic::factory()->create();
        Material::factory()->for($topic)->create(['section_id' => $topic->section_id]);
        $wanted = Exam::factory()->for($topic)->create(['title' => 'Latihan Numerik Terbit']);
        Exam::factory()->for($topic)->create(['title' => 'Latihan Numerik Draft', 'status' => 'draft']);
        Exam::factory()->create(['title' => 'Latihan Topik Lain']);
        $archived = Exam::factory()->for($topic)->create(['title' => 'Latihan Arsip']);
        $archived->delete();
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('admin.materials.index', ['topic_id' => $topic->id]))
            ->assertSee('Latihan Soal Topik')->assertSee($wanted->title)->assertSee('Latihan Numerik Draft')
            ->assertDontSee('Latihan Topik Lain')->assertDontSee('Latihan Arsip')
            ->assertSee(route('admin.materials.quizzes.edit', $wanted));
        $this->get(route('admin.materials.quizzes.index', ['topic_id' => $topic->id, 'q' => 'Numerik', 'status' => 'published']))
            ->assertSee($wanted->title)->assertDontSee('Latihan Numerik Draft')->assertDontSee('Latihan Topik Lain')
            ->assertViewHas('exams', fn ($exams): bool => $exams->modelKeys() === [$wanted->id]);
        $this->get(route('admin.materials.quizzes.create', ['topic_id' => $topic->id]))
            ->assertViewHas('exam', fn (Exam $exam): bool => (int) $exam->topic_id === $topic->id);
        $this->get(route('admin.materials.quizzes.index', ['section_id' => Section::factory()->create()->id, 'topic_id' => $topic->id]))
            ->assertNotFound();
    }

    public function test_catalog_types_are_enforced_and_tryout_crud_uses_the_tryout_workspace(): void
    {
        $question = Question::factory()->published()->create();
        $data = ['title' => 'Tryout Baru', 'slug' => 'tryout-baru', 'kind' => 'tryout', 'status' => 'published',
            'question_count' => 1, 'duration_minutes' => 30, 'points_per_correct' => 5, 'penalty_wrong' => 0,
            'random_questions' => true, 'random_options' => true, 'question_ids' => [$question->id]];
        $this->actingAs(User::factory()->admin()->create());

        $this->post(route('admin.materials.quizzes.store'), $data)->assertSessionHasErrors('kind');
        $this->post(route('admin.exams.store'), array_replace($data, ['kind' => 'quiz']))->assertSessionHasErrors('kind');
        $this->assertDatabaseCount('exams', 0);
        $this->post(route('admin.exams.store'), $data)->assertSessionHasNoErrors();
        $exam = Exam::sole();
        $this->assertSame('tryout', $exam->kind);
        $this->assertSame([$question->id], $exam->questions()->pluck('questions.id')->all());
        $this->put(route('admin.exams.update', $exam), array_replace($data, ['duration_minutes' => 45]))
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.exams.edit', $exam));
        $this->assertSame(45, $exam->fresh()->duration_minutes);
        $this->delete(route('admin.materials.quizzes.destroy', $exam))->assertNotFound();
        $this->assertNotSoftDeleted($exam);
        $this->delete(route('admin.exams.destroy', $exam))->assertRedirect(route('admin.exams.index'));
        $this->assertSoftDeleted($exam);
    }

    public function test_material_quiz_workspace_requires_admin_access(): void
    {
        $this->get(route('admin.materials.quizzes.index'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())->get(route('admin.materials.quizzes.index'))->assertForbidden();
    }
}
