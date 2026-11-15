<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ExamKind;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\Section;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ExamQuestionTopicVisibilityTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith([ExamKind::Tryout])]
    #[TestWith([ExamKind::Practice])]
    #[TestWith([ExamKind::Quiz])]
    public function test_admin_can_hide_topics_from_an_active_attempt_and_keep_them_in_review(ExamKind $kind): void
    {
        $exam = Exam::factory()->create([
            'kind' => $kind->value,
            'duration_minutes' => $kind === ExamKind::Quiz ? 0 : 30,
        ]);
        $question = Question::factory()->published()->for(Section::factory()->state(['code' => 'TWK']))
            ->create(['title' => 'Nasionalisme', 'body' => 'Apa contoh menjaga persatuan?']);
        $exam->questions()->attach($question);
        $participant = User::factory()->create();
        UserPackage::factory()->for($participant)->create(['tier' => 'premium', 'tryout_quota' => null]);
        $attempt = ExamAttempt::resumeOrStart($participant, $exam);

        $this->actingAs($participant)->get($attempt->attemptUrl())
            ->assertSee('"topic":"Nasionalisme"', false);

        $editUrl = route($kind->adminRoutePrefix().'.edit', $exam);
        $this->actingAs(User::factory()->admin()->create())->get($editUrl)
            ->assertSee('Tampilkan judul soal')
            ->assertSee('aria-describedby="exam-question-topic-help" checked', false);

        $this->put(route($kind->adminRoutePrefix().'.update', $exam), $this->payload($exam) + ['show_question_topic' => '0'])
            ->assertRedirect($editUrl);
        $this->assertDatabaseHas('exams', ['id' => $exam->id, 'show_question_topic' => false]);

        $this->actingAs($participant)->get($attempt->attemptUrl())
            ->assertDontSee('Nasionalisme')
            ->assertSee('"section":"TWK"', false)
            ->assertSee('Apa contoh menjaga persatuan?');

        $attempt->complete();
        $this->get($attempt->resultUrl())->assertSeeText('Nasionalisme');
    }

    public function test_untitled_question_has_no_topic_label_even_with_topic_visibility_enabled(): void
    {
        $exam = Exam::factory()->tryout()->create(['show_question_topic' => true]);
        $question = Question::factory()->published()->create(['title' => null, 'body' => 'Berapa hasil dari dua ditambah dua?']);
        $exam->questions()->attach($question);
        $participant = User::factory()->create();
        $attempt = ExamAttempt::resumeOrStart($participant, $exam);

        $this->actingAs($participant)->get($attempt->attemptUrl())
            ->assertSee('Berapa hasil dari dua ditambah dua?')
            ->assertDontSee('"topic":', false)
            ->assertDontSee('Soal #'.$question->id);
    }

    public function test_invalid_topic_visibility_keeps_the_saved_setting(): void
    {
        $exam = Exam::factory()->tryout()->create(['status' => 'draft', 'show_question_topic' => false]);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.exams.update', $exam), $this->payload($exam) + ['show_question_topic' => 'invalid'])
            ->assertSessionHasErrors(['show_question_topic' => 'Pilih apakah label topik soal ditampilkan atau disembunyikan.']);

        $this->assertDatabaseHas('exams', ['id' => $exam->id, 'show_question_topic' => false]);
    }

    /** @return array<string, mixed> */
    private function payload(Exam $exam): array
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
        ];
    }
}
