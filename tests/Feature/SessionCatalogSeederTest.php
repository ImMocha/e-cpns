<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MaterialAccess;
use App\Enums\QuestionScoring;
use App\Enums\QuestionStatus;
use App\Jobs\SendContentEmail;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\Section;
use App\Models\User;
use App\Models\UserPackage;
use Database\Seeders\PracticeSessionSeeder;
use Database\Seeders\SectionSeeder;
use Database\Seeders\SessionCatalogSeeder;
use Database\Seeders\SessionQuestionSeeder;
use Database\Seeders\TryoutSessionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SessionCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_sessions_have_the_requested_field_composition_and_can_start(): void
    {
        $this->seed(SectionSeeder::class);
        $this->seed(SessionCatalogSeeder::class);
        $user = User::factory()->create();
        UserPackage::factory()->for($user)->create(['tier' => 'premium', 'tryout_quota' => null]);
        $this->actingAs($user);
        $expected = [
            'contoh-tryout-skd-a' => ['tryout', 100, ['TWK' => 30, 'TIU' => 35, 'TKP' => 45]],
            'contoh-tryout-skd-b' => ['tryout', 100, ['TWK' => 30, 'TIU' => 35, 'TKP' => 45]],
            'contoh-latihan-skd-campuran-01' => ['practice', 30, ['TWK' => 10, 'TIU' => 10, 'TKP' => 10]],
            'contoh-latihan-skd-campuran-02' => ['practice', 30, ['TWK' => 10, 'TIU' => 10, 'TKP' => 10]],
            'contoh-latihan-skd-lengkap' => ['practice', 100, ['TWK' => 30, 'TIU' => 35, 'TKP' => 45]],
            'contoh-latihan-twk' => ['practice', 30, ['TWK' => 30]],
            'contoh-latihan-tiu' => ['practice', 35, ['TIU' => 35]],
            'contoh-latihan-tkp' => ['practice', 40, ['TKP' => 45]],
        ];

        $this->assertDatabaseCount('exams', 8);
        $this->assertDatabaseCount('questions', 110);
        foreach ($expected as $slug => [$kind, $minutes, $composition]) {
            $exam = Exam::query()->where('slug', $slug)->firstOrFail();
            $this->assertSame($kind, $exam->kind);
            $this->assertSame($minutes, $exam->duration_minutes);
            $this->assertSame(MaterialAccess::Premium, $exam->access_level);
            $this->assertSame(QuestionStatus::Published, $exam->status);
            $this->assertNull($exam->topic_id);
            $this->assertFalse($exam->requires_topic_completion);
            $this->assertSame(array_sum($composition), $exam->question_count);
            $definition = $exam->definition();
            $this->assertSame(array_sum($composition), count($definition['questions']));
            $actual = array_count_values(array_column($definition['questions'], 'section'));
            ksort($actual);
            ksort($composition);
            $this->assertSame($composition, $actual);
            $this->assertSame($exam->question_count * 5, $exam->maximumScore());
            $this->post(route($kind === 'tryout' ? 'tryouts.start' : 'practices.start', $slug))->assertSessionHasNoErrors()->assertRedirect();
        }
        $this->assertDatabaseCount('exam_attempts', 8);
        $this->get(route('quizzes.index'))->assertSee('Contoh: Latihan Khusus TWK')->assertSee('Contoh: Latihan Campuran SKD 01')
            ->assertDontSee('Contoh: Tryout SKD A');
        $this->get(route('tryouts.index'))->assertSee('Contoh: Tryout SKD A')->assertSee('Contoh: Tryout SKD B');
    }

    public function test_seeded_questions_have_unique_content_valid_keys_weighted_tkp_and_explanations(): void
    {
        $this->seed(SectionSeeder::class);
        $this->seed(SessionQuestionSeeder::class);
        $questions = Question::query()->with('section')->get();

        $this->assertSame(110, $questions->pluck('body')->unique()->count());
        $this->assertSame(110, $questions->pluck('source_key')->unique()->count());
        $this->assertSame(45, $questions->where('scoring_type', QuestionScoring::Weighted)->count());
        foreach ($questions as $question) {
            $this->assertCount(5, array_unique($question->options));
            $this->assertNotEmpty(strip_tags($question->explanation));
            $this->assertSame(QuestionStatus::Published, $question->status);
            if ($question->section->code === 'TKP') {
                $scores = $question->option_scores;
                sort($scores);
                $this->assertSame([1, 2, 3, 4, 5], $scores);
                $this->assertNull($question->correct_option);
            } else {
                $this->assertArrayHasKey($question->correct_option, $question->options);
                $this->assertNull($question->option_scores);
            }
        }
        $discount = Question::query()->where('source_key', 'contoh-skd:TIU:002')->firstOrFail();
        $this->assertSame('102000', $discount->options[$discount->correct_option]);
        $ratio = Question::query()->where('source_key', 'contoh-skd:TIU:003')->firstOrFail();
        $this->assertSame('40', $ratio->options[$ratio->correct_option]);
    }

    public function test_complete_tryout_can_earn_550_points_and_pass_all_three_fields(): void
    {
        $this->seed(SectionSeeder::class);
        $this->seed(SessionCatalogSeeder::class);
        $exam = Exam::query()->where('slug', 'contoh-tryout-skd-a')->firstOrFail();
        $user = User::factory()->create();
        UserPackage::factory()->for($user)->create(['tier' => 'premium', 'tryout_quota' => null]);
        $attempt = ExamAttempt::resumeOrStart($user, $exam);
        $answers = [];
        foreach ($attempt->question_snapshot['questions'] as $question) {
            $answers[$question['id']] = $question['scoringType'] === 'weighted'
                ? array_search(5, $question['optionScores'], true) : $question['answer'];
        }
        $attempt->update(['answers' => $answers]);

        $attempt->complete();

        $this->assertSame(550, $attempt->score);
        $this->assertSame(150, $attempt->section_scores['TWK']['score']);
        $this->assertSame(175, $attempt->section_scores['TIU']['score']);
        $this->assertSame(225, $attempt->section_scores['TKP']['score']);
        $this->assertTrue($attempt->canPrintCertificate());
    }

    public function test_mixed_practice_series_have_different_question_sets_and_tryout_order_varies(): void
    {
        $this->seed(SectionSeeder::class);
        $this->seed(SessionCatalogSeeder::class);
        $first = Exam::query()->where('slug', 'contoh-latihan-skd-campuran-01')->firstOrFail()->questions()->pluck('questions.id');
        $second = Exam::query()->where('slug', 'contoh-latihan-skd-campuran-02')->firstOrFail()->questions()->pluck('questions.id');
        $tryoutA = Exam::query()->where('slug', 'contoh-tryout-skd-a')->firstOrFail()->questions()->pluck('questions.id')->all();
        $tryoutB = Exam::query()->where('slug', 'contoh-tryout-skd-b')->firstOrFail()->questions()->pluck('questions.id')->all();

        $this->assertSame([], $first->intersect($second)->all());
        $this->assertNotSame($tryoutA, $tryoutB);
        sort($tryoutA);
        sort($tryoutB);
        $this->assertSame($tryoutA, $tryoutB);
    }

    public function test_reseeding_preserves_admin_edits_assignments_archives_and_other_question_banks(): void
    {
        $this->seed(SectionSeeder::class);
        $original = Question::factory()->published()->create(['source_key' => 'original-premium-question']);
        $this->seed(SessionCatalogSeeder::class);
        $exam = Exam::query()->where('slug', 'contoh-latihan-twk')->firstOrFail();
        $question = $exam->questions()->firstOrFail();
        $exam->update(['title' => 'Latihan milik admin', 'duration_minutes' => 20, 'status' => 'draft']);
        $question->update(['body' => '<p>Isi dari admin.</p>']);
        $exam->questions()->detach($question);
        $question->delete();
        $tryout = Exam::query()->where('slug', 'contoh-tryout-skd-b')->firstOrFail();
        $tryout->delete();

        $this->seed(SessionCatalogSeeder::class);

        $this->assertSame(111, Question::withTrashed()->count());
        $this->assertSame(8, Exam::withTrashed()->count());
        $this->assertSame('Latihan milik admin', $exam->fresh()->title);
        $this->assertSame(20, $exam->fresh()->duration_minutes);
        $this->assertSame(QuestionStatus::Draft, $exam->fresh()->status);
        $this->assertSame(29, $exam->questions()->count());
        $this->assertSame('<p>Isi dari admin.</p>', $question->fresh()->body);
        $this->assertSoftDeleted($question);
        $this->assertSoftDeleted($tryout);
        $this->assertSame(0, $original->exams()->count());
    }

    public function test_missing_or_inactive_required_fields_do_not_create_partial_banks(): void
    {
        $this->seed(SessionCatalogSeeder::class);
        $this->assertDatabaseCount('questions', 0);
        $this->assertDatabaseCount('exams', 0);
        $this->seed(SectionSeeder::class);
        Section::query()->where('code', 'TIU')->update(['is_active' => false]);

        $this->seed(SessionCatalogSeeder::class);

        $this->assertDatabaseCount('questions', 0);
        $this->assertDatabaseCount('exams', 0);
    }

    public function test_unavailable_seed_questions_do_not_create_incomplete_sessions(): void
    {
        $this->seed(SectionSeeder::class);
        $this->seed(SessionQuestionSeeder::class);
        Question::query()->where('source_key', 'contoh-skd:TIU:001')->firstOrFail()->update(['status' => 'draft']);

        $this->seed([TryoutSessionSeeder::class, PracticeSessionSeeder::class]);

        $this->assertDatabaseMissing('exams', ['kind' => 'tryout']);
        $this->assertDatabaseMissing('exams', ['slug' => 'contoh-latihan-tiu']);
        $this->assertDatabaseMissing('exams', ['slug' => 'contoh-latihan-skd-lengkap']);
        $this->assertDatabaseHas('exams', ['slug' => 'contoh-latihan-twk']);
        $this->assertDatabaseHas('exams', ['slug' => 'contoh-latihan-tkp']);
        foreach (Exam::all() as $exam) {
            $this->assertCount($exam->question_count, $exam->definition()['questions']);
        }
    }

    public function test_catalog_seeding_does_not_send_content_notifications(): void
    {
        $this->seed(SectionSeeder::class);
        User::factory()->admin()->create();
        Queue::fake([SendContentEmail::class]);

        $this->seed(SessionCatalogSeeder::class);

        Queue::assertNotPushed(SendContentEmail::class);
    }
}
