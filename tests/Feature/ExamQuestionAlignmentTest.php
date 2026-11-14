<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ExamKind;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ExamQuestionAlignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_session_editor_defaults_to_justified_questions(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.exams.create'))
            ->assertSee('name="question_alignment" value="justify" checked', false);
    }

    #[DataProvider('sessionKinds')]
    public function test_admin_alignment_applies_to_the_entire_session_and_its_review(ExamKind $kind, string $alignment): void
    {
        $exam = Exam::factory()->create([
            'kind' => $kind->value,
            'duration_minutes' => $kind === ExamKind::Quiz ? 0 : 30,
            'topic_id' => $kind === ExamKind::Quiz ? Topic::factory()->create()->id : null,
            'question_count' => 2,
            'random_questions' => false,
        ]);
        $questions = Question::factory()->published()->count(2)->create();
        $exam->questions()->attach($questions);
        $reader = User::factory()->create();
        UserPackage::factory()->for($reader)->create(['tier' => 'premium', 'tryout_quota' => null]);
        $attempt = ExamAttempt::resumeOrStart($reader, $exam);
        $this->actingAs($reader)->get($attempt->attemptUrl())
            ->assertSee('data-question-alignment="justify"', false);

        $editUrl = route($kind->adminRoutePrefix().'.edit', $exam);
        $this->actingAs(User::factory()->admin()->create())
            ->put(route($kind->adminRoutePrefix().'.update', $exam), $this->payload($exam, $alignment))
            ->assertRedirect($editUrl);

        $this->get($editUrl)
            ->assertSeeInOrder(['Perataan teks soal', 'Urutan soal dipilih'])
            ->assertSee('name="question_alignment" value="'.$alignment.'" checked', false);

        $this->actingAs($reader)->get($attempt->attemptUrl())
            ->assertSee('data-question-alignment="'.$alignment.'"', false);
        $attempt->complete();
        $this->get($attempt->resultUrl())
            ->assertSee('data-question-alignment="'.$alignment.'"', false);
    }

    public static function sessionKinds(): array
    {
        return [
            'latihan topik' => [ExamKind::Quiz, 'right'],
            'latihan soal' => [ExamKind::Practice, 'left'],
            'tryout' => [ExamKind::Tryout, 'right'],
        ];
    }

    public function test_invalid_alignment_does_not_overwrite_the_saved_choice(): void
    {
        $exam = Exam::factory()->tryout()->create(['status' => 'draft', 'question_alignment' => 'left']);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.exams.update', $exam), $this->payload($exam, 'center'))
            ->assertSessionHasErrors(['question_alignment' => 'Pilih rata kiri, rata kanan, atau rata kiri-kanan.']);

        $this->assertDatabaseHas('exams', ['id' => $exam->id, 'question_alignment' => 'left']);
    }

    /** @return array<string, mixed> */
    private function payload(Exam $exam, string $alignment): array
    {
        return [
            'title' => $exam->title,
            'kind' => $exam->kind,
            'status' => $exam->status->value,
            'question_count' => $exam->question_count,
            'duration_minutes' => $exam->duration_minutes,
            'points_per_correct' => $exam->points_per_correct,
            'penalty_wrong' => $exam->penalty_wrong,
            'random_questions' => $exam->random_questions,
            'random_options' => $exam->random_options,
            'question_ids' => $exam->questions()->pluck('questions.id')->all(),
            'question_alignment' => $alignment,
        ];
    }
}
