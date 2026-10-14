<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class QuestionExamAssignmentTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function payload(Section $section, array $overrides = []): array
    {
        return array_replace(['title' => 'Soal Numerik', 'section_id' => $section->id,
            'body' => '<p>Berapa hasil 12 + 8?</p>', 'options' => ['18', '20'], 'correct_option' => 1,
            'explanation' => '<p>12 + 8 = 20.</p>', 'difficulty' => 'easy', 'status' => 'published'], $overrides);
    }

    public function test_list_shows_each_usage_and_excludes_archived_sessions(): void
    {
        $question = Question::factory()->published()->create(['title' => 'Soal Bersama']);
        $quiz = Exam::factory()->create(['title' => 'Latihan <script>buruk()</script>', 'status' => 'draft']);
        $tryout = Exam::factory()->tryout()->create(['title' => 'Tryout Mingguan']);
        $archived = Exam::factory()->create(['title' => 'Sesi Diarsipkan']);
        $question->exams()->attach([$quiz->id, $tryout->id, $archived->id]);
        $archived->delete();
        Question::factory()->create(['title' => 'Soal Tanpa Sesi']);

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.questions.index'))
            ->assertSee('Digunakan di')->assertSee($quiz->title)->assertSee($tryout->title)
            ->assertSee(route('admin.materials.quizzes.edit', $quiz))->assertSee(route('admin.exams.edit', $tryout))
            ->assertDontSee($quiz->title, false)->assertDontSee($archived->title)
            ->assertSee('Belum masuk sesi')->assertSee('Pilih sesi tujuan');
    }

    public function test_usage_and_session_filters_match_the_same_session_and_other_filters(): void
    {
        $this->freezeTime();
        $section = Section::factory()->create();
        $quiz = Exam::factory()->create();
        $tryout = Exam::factory()->tryout()->create();
        $quizOnly = Question::factory()->published()->for($section)->create(['title' => 'Pola Latihan']);
        $tryoutOnly = Question::factory()->published()->for($section)->create(['title' => 'Pola Tryout']);
        $both = Question::factory()->published()->for($section)->create(['title' => 'Pola Bersama']);
        $unused = Question::factory()->published()->for($section)->create(['title' => 'Pola Belum Dipakai']);
        $quiz->questions()->attach([$quizOnly->id, $both->id]);
        $tryout->questions()->attach([$tryoutOnly->id, $both->id]);
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('admin.questions.index', ['usage' => 'quiz']))
            ->assertViewHas('questions', fn ($questions): bool => $questions->modelKeys() === [$both->id, $quizOnly->id]);
        $this->get(route('admin.questions.index', ['usage' => 'tryout', 'q' => 'Pola', 'section_id' => $section->id, 'difficulty' => 'easy', 'status' => 'published']))
            ->assertViewHas('questions', fn ($questions): bool => $questions->modelKeys() === [$both->id, $tryoutOnly->id]);
        $this->get(route('admin.questions.index', ['usage' => 'unused']))
            ->assertViewHas('questions', fn ($questions): bool => $questions->modelKeys() === [$unused->id]);
        $this->get(route('admin.questions.index', ['exam_id' => $quiz->id]))
            ->assertViewHas('questions', fn ($questions): bool => $questions->modelKeys() === [$both->id, $quizOnly->id]);
        $this->get(route('admin.questions.index', ['usage' => 'tryout', 'exam_id' => $quiz->id]))
            ->assertSee('Belum ada soal yang sesuai');
    }

    public function test_question_can_be_created_with_multiple_sessions_and_edit_shows_saved_selection(): void
    {
        $section = Section::factory()->create();
        $quiz = Exam::factory()->create(['status' => 'draft']);
        $tryout = Exam::factory()->tryout()->create(['status' => 'draft']);
        $this->actingAs(User::factory()->admin()->create());

        $this->post(route('admin.questions.store'), $this->payload($section, ['exam_ids' => [$quiz->id, $tryout->id]]))
            ->assertSessionHasNoErrors();
        $question = Question::sole();
        $this->assertDatabaseHas('exam_question', ['exam_id' => $quiz->id, 'question_id' => $question->id]);
        $this->assertDatabaseHas('exam_question', ['exam_id' => $tryout->id, 'question_id' => $question->id]);
        $this->get(route('admin.questions.edit', $question))->assertSee('Sesi tujuan soal')
            ->assertViewHas('selectedExamIds', [$quiz->id, $tryout->id]);
    }

    public function test_assignment_changes_preserve_other_questions_and_requests_without_assignment_fields(): void
    {
        $question = Question::factory()->published()->create();
        $spare = Question::factory()->published()->create();
        $quiz = Exam::factory()->create();
        $tryout = Exam::factory()->tryout()->create();
        $quiz->questions()->attach([$question->id, $spare->id]);
        $this->actingAs(User::factory()->admin()->create());

        $this->put(route('admin.questions.update', $question), $this->payload($question->section, ['exam_ids' => [$tryout->id]]))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('exam_question', ['exam_id' => $quiz->id, 'question_id' => $question->id]);
        $this->assertDatabaseHas('exam_question', ['exam_id' => $quiz->id, 'question_id' => $spare->id]);
        $this->assertDatabaseHas('exam_question', ['exam_id' => $tryout->id, 'question_id' => $question->id]);

        $this->put(route('admin.questions.update', $question), $this->payload($question->section, ['title' => 'Judul Revisi']))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('exam_question', ['exam_id' => $tryout->id, 'question_id' => $question->id]);
        $this->assertDatabaseHas('questions', ['id' => $question->id, 'title' => 'Judul Revisi']);
    }

    public function test_unchecking_every_session_clears_active_assignments_and_preserves_archived_membership(): void
    {
        $question = Question::factory()->published()->create();
        $draft = Exam::factory()->create(['status' => 'draft']);
        $archived = Exam::factory()->create();
        $question->exams()->attach([$draft->id, $archived->id]);
        $archived->delete();

        $this->actingAs(User::factory()->admin()->create())->put(route('admin.questions.update', $question),
            $this->payload($question->section, ['exam_ids' => '']))->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('exam_question', ['exam_id' => $draft->id, 'question_id' => $question->id]);
        $this->assertDatabaseHas('exam_question', ['exam_id' => $archived->id, 'question_id' => $question->id]);
        $this->get(route('admin.questions.index', ['usage' => 'unused']))->assertSee('Soal Numerik');
    }

    public function test_question_cannot_be_removed_if_published_session_would_have_insufficient_questions(): void
    {
        $question = Question::factory()->published()->create(['title' => 'Judul Awal']);
        $exam = Exam::factory()->create(['title' => 'Latihan Wajib']);
        $exam->questions()->attach($question);

        $this->actingAs(User::factory()->admin()->create())->put(route('admin.questions.update', $question),
            $this->payload($question->section, ['exam_ids' => []]))
            ->assertSessionHasErrors(['exam_ids' => 'Soal tidak dapat dilepas dari "Latihan Wajib" karena jumlah soal terbit tidak mencukupi. Tambahkan soal pengganti atau ubah jumlah soal pada sesi terlebih dahulu.']);

        $this->assertDatabaseHas('questions', ['id' => $question->id, 'title' => 'Judul Awal']);
        $this->assertDatabaseHas('exam_question', ['exam_id' => $exam->id, 'question_id' => $question->id]);
    }

    #[DataProvider('invalidAssignments')]
    public function test_invalid_assignments_do_not_change_question_or_existing_membership(mixed $value, string $field, string $message): void
    {
        $question = Question::factory()->published()->create(['title' => 'Judul Awal']);
        $exam = Exam::factory()->create();
        $exam->questions()->attach($question);

        $this->actingAs(User::factory()->admin()->create())->put(route('admin.questions.update', $question),
            $this->payload($question->section, ['exam_ids' => $value]))->assertSessionHasErrors([$field => $message]);

        $this->assertDatabaseHas('questions', ['id' => $question->id, 'title' => 'Judul Awal']);
        $this->assertDatabaseHas('exam_question', ['exam_id' => $exam->id, 'question_id' => $question->id]);
    }

    public static function invalidAssignments(): array
    {
        return [
            'not an array' => ['wrong', 'exam_ids', 'Pilihan sesi tidak valid.'],
            'not a list' => [['bad' => 999999], 'exam_ids', 'Urutan pilihan sesi tidak valid.'],
            'unknown session' => [[999999], 'exam_ids.0', 'Sesi yang dipilih sudah dihapus atau tidak tersedia.'],
            'nested selection' => [[['id' => 1]], 'exam_ids.0', 'Pilihan sesi tidak valid.'],
            'duplicate session' => [[999999, 999999], 'exam_ids.0', 'Sesi yang sama tidak boleh dipilih dua kali.'],
        ];
    }

    public function test_deleted_sessions_cannot_be_assigned_or_used_as_a_filter(): void
    {
        $exam = Exam::factory()->create();
        $exam->delete();
        $section = Section::factory()->create();
        $this->actingAs(User::factory()->admin()->create());

        $this->post(route('admin.questions.store'), $this->payload($section, ['exam_ids' => [$exam->id]]))
            ->assertSessionHasErrors(['exam_ids.0' => 'Sesi yang dipilih sudah dihapus atau tidak tersedia.']);
        $this->assertDatabaseCount('questions', 0);
        $this->getJson(route('admin.questions.index', ['exam_id' => $exam->id]))
            ->assertUnprocessable()->assertJsonValidationErrors('exam_id');
    }

    public function test_participants_cannot_change_question_assignments(): void
    {
        $question = Question::factory()->published()->create();
        $exam = Exam::factory()->create(['status' => 'draft']);

        $this->actingAs(User::factory()->create())->put(route('admin.questions.update', $question),
            $this->payload($question->section, ['exam_ids' => [$exam->id]]))->assertForbidden();

        $this->assertDatabaseCount('exam_question', 0);
    }

    public function test_assignment_changes_only_affect_new_attempts(): void
    {
        $question = Question::factory()->published()->create();
        $replacement = Question::factory()->published()->create();
        $exam = Exam::factory()->create(['random_questions' => false]);
        $exam->questions()->attach([$question->id, $replacement->id]);
        $attempt = ExamAttempt::resumeOrStart(User::factory()->create(), $exam);
        $originalSnapshot = $attempt->question_snapshot;

        $this->actingAs(User::factory()->admin()->create())->put(route('admin.questions.update', $question),
            $this->payload($question->section, ['exam_ids' => []]))->assertSessionHasNoErrors();

        $this->assertSame($originalSnapshot, $attempt->fresh()->question_snapshot);
        $this->assertSame($replacement->id, $exam->definition()['questions'][0]['id']);
    }
}
