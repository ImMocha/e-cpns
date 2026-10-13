<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Question;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionBankNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_navigation_follows_publication_order_with_id_ties_across_list_pages(): void
    {
        $this->freezeTime();
        $newest = Question::factory()->published()->create();
        $questions = Question::factory()->published()->count(13)->create(['published_at' => now()->subDay()]);
        $this->actingAs(User::factory()->create());

        $this->get(route('question-bank.show', $questions[2]))
            ->assertSee('Soal 12 dari 14')
            ->assertSee(route('question-bank.show', $questions[3]))
            ->assertSee(route('question-bank.show', $questions[1]));

        $this->get(route('question-bank.index', ['page' => 2]))
            ->assertSee(route('question-bank.show', ['question' => $questions[1], 'page' => 2]));
        $this->get(route('question-bank.show', ['question' => $questions[1], 'page' => 2]))
            ->assertSee('Soal 13 dari 14')
            ->assertSee(route('question-bank.show', ['question' => $questions[2], 'page' => 2]))
            ->assertSee(route('question-bank.show', ['question' => $questions[0], 'page' => 2]));

        $this->get(route('question-bank.show', $newest))->assertSee('Soal 1 dari 14')
            ->assertViewHas('navigation', fn (array $navigation): bool => $navigation['previous'] === null && $navigation['next']?->id === $questions[12]->id);
        $this->get(route('question-bank.show', $questions[0]))->assertSee('Soal 14 dari 14')
            ->assertSee('Kembali ke daftar')
            ->assertViewHas('navigation', fn (array $navigation): bool => $navigation['next'] === null && $navigation['previous']?->id === $questions[1]->id);
    }

    public function test_navigation_skips_unpublished_future_inactive_and_archived_questions(): void
    {
        $this->freezeTime();
        $older = Question::factory()->published()->create(['published_at' => now()->subDays(2)]);
        Question::factory()->create();
        Question::factory()->published()->create(['published_at' => now()->addDay()]);
        Question::factory()->published()->for(Section::factory()->inactive())->create();
        $archived = Question::factory()->published()->create();
        $archived->delete();
        $newer = Question::factory()->published()->create();

        $this->actingAs(User::factory()->create())->get(route('question-bank.show', $newer))
            ->assertSee('Soal 1 dari 2')
            ->assertViewHas('navigation', fn (array $navigation): bool => $navigation['next']?->id === $older->id);
    }

    public function test_filters_are_preserved_in_list_navigation_answer_and_return_links(): void
    {
        $this->freezeTime();
        $section = Section::factory()->create();
        $older = Question::factory()->published()->for($section)->create(['title' => 'Pola Pertama', 'published_at' => now()->subDay()]);
        $newer = Question::factory()->published()->for($section)->create(['title' => 'Pola Kedua']);
        Question::factory()->published()->for($section)->create(['title' => 'Judul Berbeda']);
        Question::factory()->published()->for($section)->create(['title' => 'Pola Sulit', 'difficulty' => 'hard']);
        Question::factory()->published()->create(['title' => 'Pola Bidang Lain']);
        $filters = ['q' => 'Pola', 'section_id' => $section->id, 'difficulty' => 'easy'];
        $this->actingAs(User::factory()->create());

        $this->get(route('question-bank.index', $filters))
            ->assertSee(route('question-bank.show', ['question' => $newer, ...$filters]));
        $filters['page'] = 1;
        $showUrl = route('question-bank.show', ['question' => $newer, ...$filters]);
        $answerUrl = route('question-bank.answer', ['question' => $newer, ...$filters]);
        $nextUrl = route('question-bank.show', ['question' => $older, ...$filters]);
        $this->get($showUrl)->assertSee('Soal 1 dari 2')->assertSee($nextUrl)
            ->assertSee($answerUrl)->assertSee(route('question-bank.index', $filters));

        $this->post($answerUrl, ['option' => 1, 'revision' => $newer->revision()])
            ->assertRedirect($showUrl)->assertSessionHasNoErrors()
            ->assertSessionHas('question_feedback', fn (array $feedback): bool => $feedback['question_id'] === $newer->id && $feedback['option'] === 1);
        $this->get($showUrl)->assertSee('Jawabanmu benar!')->assertSee('Lanjut ke soal berikutnya')->assertSee($nextUrl);
        $this->get($nextUrl)->assertSee('Soal 2 dari 2')->assertDontSee('question-answer-option--correct', false)
            ->assertDontSee('12 + 8 = 20.');
    }

    public function test_single_question_has_no_previous_or_next_and_can_return_after_answer(): void
    {
        $question = Question::factory()->published()->create();
        $this->actingAs(User::factory()->create());

        $this->get(route('question-bank.show', $question))->assertSee('Soal 1 dari 1')
            ->assertViewHas('navigation', ['position' => 1, 'total' => 1, 'previous' => null, 'next' => null]);
        $this->post(route('question-bank.answer', $question), ['option' => 1, 'revision' => $question->revision()])
            ->assertRedirect(route('question-bank.show', $question));
        $this->get(route('question-bank.show', $question))->assertSee('Kamu sudah sampai di soal terakhir.')->assertSee('Pilih soal lainnya');
    }

    public function test_question_outside_current_filters_does_not_show_misleading_navigation(): void
    {
        $question = Question::factory()->published()->create(['difficulty' => 'easy']);
        Question::factory()->published()->create(['difficulty' => 'hard']);

        $this->actingAs(User::factory()->create())->get(route('question-bank.show', ['question' => $question, 'difficulty' => 'hard']))
            ->assertDontSee('Navigasi bank soal')->assertSee(route('question-bank.index', ['difficulty' => 'hard']));
    }

    public function test_admin_draft_preview_does_not_join_participant_navigation(): void
    {
        $draft = Question::factory()->create();
        Question::factory()->published()->create();

        $this->actingAs(User::factory()->admin()->create())->get(route('question-bank.show', $draft))
            ->assertSee('Preview admin')->assertDontSee('Navigasi bank soal');
    }

    public function test_invalid_navigation_filters_are_rejected_before_answer_feedback(): void
    {
        $question = Question::factory()->published()->create();
        $this->actingAs(User::factory()->create());

        $this->getJson(route('question-bank.show', ['question' => $question, 'q' => ['unexpected']]))
            ->assertUnprocessable()->assertJsonValidationErrors('q');
        $this->postJson(route('question-bank.answer', ['question' => $question, 'page' => 0]), ['option' => 1, 'revision' => $question->revision()])
            ->assertUnprocessable()->assertJsonValidationErrors('page')->assertSessionMissing('question_feedback');
    }
}
