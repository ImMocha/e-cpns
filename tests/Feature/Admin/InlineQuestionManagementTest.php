<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\Section;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InlineQuestionManagementTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string, string}> */
    public static function sessionKinds(): array
    {
        return ['topic quiz' => ['quiz', 'admin.materials.quizzes'], 'tryout' => ['tryout', 'admin.exams'],
            'practice' => ['practice', 'admin.practices']];
    }

    /** @return array<string, mixed> */
    private function payload(Section $section): array
    {
        return ['title' => 'Soal dari Editor Sesi', 'section_id' => $section->id,
            'body' => '<p>Berapa hasil 12 + 8?</p>', 'options' => ['18', '20', '22'], 'correct_option' => 1,
            'explanation' => '<p>12 + 8 = 20.</p>', 'difficulty' => 'easy', 'status' => 'published'];
    }

    #[DataProvider('sessionKinds')]
    public function test_both_session_editors_include_direct_question_management_without_leaking_session_old_input(string $kind, string $prefix): void
    {
        $topic = $kind === 'quiz' ? Topic::factory()->create() : null;
        $exam = Exam::factory()->create(['kind' => $kind, 'topic_id' => $topic?->id]);
        $question = Question::factory()->published()->create(['title' => 'Soal <script>buruk()</script>']);
        $exam->questions()->attach($question);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->withSession(['_old_input' => ['title' => 'Pengaturan sesi belum tersimpan']])
            ->get(route($prefix.'.edit', $exam))->assertSee('Tambah Soal')->assertSee('Hanya soal dipilih')
            ->assertSee('data-inline-question-form', false)->assertSee($question->title)->assertDontSee($question->title, false);

        $dialog = substr($response->getContent(), strpos($response->getContent(), '<dialog class="exam-question-dialog"'));
        $this->assertStringNotContainsString('value="Pengaturan sesi belum tersimpan"', $dialog);
        $this->assertStringContainsString('name="video"', $dialog);
        $this->assertStringContainsString('name="correct_option"', $dialog);
    }

    #[DataProvider('sessionKinds')]
    public function test_question_dropdowns_show_each_source_session_and_keep_unassigned_questions_accessible(string $kind, string $prefix): void
    {
        $current = Exam::factory()->create(['kind' => $kind]);
        $tryout = Exam::factory()->tryout()->create(['title' => 'Tryout A <script>buruk()</script>']);
        $topicQuiz = Exam::factory()->create(['title' => 'Latihan Topik Nasionalisme']);
        $practice = Exam::factory()->practice()->create(['title' => 'Latihan Soal Mandiri']);
        $archived = Exam::factory()->tryout()->create(['title' => 'Sesi sumber arsip']);
        $shared = Question::factory()->published()->create();
        $topicQuestion = Question::factory()->published()->create();
        $practiceQuestion = Question::factory()->published()->create();
        $unassigned = Question::factory()->create();
        $archivedQuestion = Question::factory()->published()->create();
        $shared->exams()->attach([$current->id, $tryout->id]);
        $topicQuestion->exams()->attach($topicQuiz);
        $practiceQuestion->exams()->attach($practice);
        $archivedQuestion->exams()->attach($archived);
        $archived->delete();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route($prefix.'.edit', $current))->assertSee('Sumber soal')->assertSee('Buka semua')
            ->assertSee($tryout->title)->assertDontSee($tryout->title, false)->assertDontSee($archived->title)
            ->assertSee('Latihan Topik')->assertSee('Latihan Soal')->assertSee('Belum masuk sesi');

        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        foreach ([$current->id => [$shared->id], $tryout->id => [$shared->id], $topicQuiz->id => [$topicQuestion->id],
            $practice->id => [$practiceQuestion->id], 'unassigned' => [$unassigned->id, $archivedQuestion->id]] as $source => $ids) {
            $key = $source === 'unassigned' ? $source : 'exam-'.$source;
            $nodes = $xpath->query('//details[@data-exam-source="'.$key.'"]//article/@data-question-id');
            $this->assertSame($ids, array_map(fn (\DOMNode $node): int => (int) $node->nodeValue, iterator_to_array($nodes)));
        }
        $this->assertSame(0, $xpath->query('//details[@data-exam-source and @open]')->length);
    }

    public function test_source_grouping_preserves_submitted_question_order_after_validation_failure(): void
    {
        $questions = Question::factory()->published()->count(2)->create();
        $first = Exam::factory()->tryout()->create(['title' => 'A sumber']);
        $second = Exam::factory()->tryout()->create(['title' => 'Z sumber']);
        $first->questions()->attach($questions[0]);
        $second->questions()->attach($questions[1]);
        $current = Exam::factory()->create();
        $order = [$questions[1]->id, $questions[0]->id];

        $this->actingAs(User::factory()->admin()->create())->withSession(['_old_input' => ['question_ids' => $order]])
            ->get(route('admin.materials.quizzes.edit', $current))->assertViewHas('questionOrder', $order)
            ->assertSee('data-question-order="'.json_encode($order).'"', false);
    }

    #[DataProvider('sessionKinds')]
    public function test_inline_creation_returns_selectable_question_with_media_and_links_it_only_when_session_is_saved(string $kind, string $prefix): void
    {
        Storage::fake('local');
        $section = Section::factory()->create();
        $topic = $kind === 'quiz' ? Topic::factory()->for($section)->create() : null;
        $exam = Exam::factory()->create(['kind' => $kind, 'topic_id' => $topic?->id, 'status' => 'draft', 'duration_minutes' => $kind === 'quiz' ? 0 : 30]);
        $data = $this->payload($section);
        $data['image'] = UploadedFile::fake()->image('soal.png');

        $response = $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.questions.store'), $data, ['Accept' => 'application/json'])
            ->assertCreated()->assertJsonPath('question.correct_option', 1)->assertJsonPath('question.status', 'published')
            ->assertJsonPath('question.usage_count', 0);
        $question = Question::sole();
        $this->assertSame($question->id, $response->json('question.id'));
        $this->assertStringContainsString('Soal dari Editor Sesi', $response->json('html'));
        Storage::disk('local')->assertExists($question->image_path);
        $this->assertSame(route('admin.questions.image', $question), $response->json('question.image_url'));
        $this->assertSame(0, $exam->questions()->count());

        $this->put(route($prefix.'.update', $exam), ['title' => $exam->title, 'slug' => $exam->slug,
            'kind' => $kind, 'status' => 'published', 'question_count' => 1, 'duration_minutes' => $exam->duration_minutes,
            'points_per_correct' => 5, 'penalty_wrong' => 0, 'random_questions' => true, 'random_options' => true,
            'question_ids' => [$question->id]])->assertSessionHasNoErrors();
        $this->assertSame([$question->id], $exam->questions()->pluck('questions.id')->all());
    }

    public function test_admin_can_load_editable_content_and_updates_preserve_other_session_assignments_and_attempt_snapshot(): void
    {
        $question = Question::factory()->published()->create();
        $quiz = Exam::factory()->create(['random_options' => false]);
        $tryout = Exam::factory()->tryout()->create();
        $question->exams()->attach([$quiz->id, $tryout->id]);
        $attempt = ExamAttempt::resumeOrStart(User::factory()->create(), $quiz);
        $snapshot = $attempt->question_snapshot;
        $this->actingAs(User::factory()->admin()->create());

        $this->getJson(route('admin.questions.show', $question))->assertOk()
            ->assertJsonPath('question.correct_option', 1)->assertJsonPath('question.usage_count', 2)
            ->assertJsonPath('question.update_url', route('admin.questions.update', $question));
        $data = array_replace($this->payload($question->section), ['title' => 'Soal setelah diedit',
            'body' => '<p>Berapa hasil 8 + 12?</p><script>buruk()</script>']);
        $this->putJson(route('admin.questions.update', $question), $data)->assertOk()
            ->assertJsonPath('question.title', 'Soal setelah diedit')->assertJsonPath('question.usage_count', 2);

        $this->assertSame([$quiz->id, $tryout->id], $question->exams()->orderBy('exams.id')->pluck('exams.id')->all());
        $this->assertStringNotContainsString('<script>', $question->fresh()->body);
        $this->assertSame($snapshot, $attempt->fresh()->question_snapshot);
    }

    public function test_inline_validation_returns_field_errors_without_overwriting_question(): void
    {
        $question = Question::factory()->published()->create();
        $data = array_replace($this->payload($question->section), ['options' => ['Sama', 'Sama'], 'explanation' => '']);

        $this->actingAs(User::factory()->admin()->create())->putJson(route('admin.questions.update', $question), $data)
            ->assertUnprocessable()->assertJsonValidationErrors(['options.0', 'options.1', 'explanation'])
            ->assertJsonPath('errors.explanation.0', 'Lengkapi pembahasan sebelum menerbitkan soal.');

        $this->assertSame(['18', '20', '22', '24', '26'], $question->fresh()->options);
    }

    public function test_inline_archive_soft_deletes_question_and_keeps_session_relation(): void
    {
        $question = Question::factory()->published()->create();
        $exam = Exam::factory()->create();
        $exam->questions()->attach($question);

        $this->actingAs(User::factory()->admin()->create())->deleteJson(route('admin.questions.destroy', $question))
            ->assertOk()->assertJsonPath('id', $question->id);

        $this->assertSoftDeleted($question);
        $this->assertSame([$question->id], $exam->questions()->withTrashed()->pluck('questions.id')->all());
        $this->getJson(route('admin.questions.show', $question))->assertNotFound();
    }

    public function test_question_editor_json_and_mutations_are_not_available_to_participants(): void
    {
        $question = Question::factory()->published()->create();
        $data = $this->payload($question->section);
        $this->getJson(route('admin.questions.show', $question))->assertUnauthorized();
        $this->actingAs(User::factory()->create());

        $this->getJson(route('admin.questions.show', $question))->assertForbidden()->assertJsonMissingPath('question.correct_option');
        $this->postJson(route('admin.questions.store'), $data)->assertForbidden();
        $this->putJson(route('admin.questions.update', $question), $data)->assertForbidden();
        $this->deleteJson(route('admin.questions.destroy', $question))->assertForbidden();
        $this->assertNotSoftDeleted($question);
        $this->assertDatabaseCount('questions', 1);
    }
}
