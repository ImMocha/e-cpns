<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\QuestionScoring;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Package;
use App\Models\Question;
use App\Models\Section;
use App\Models\User;
use Database\Seeders\PremiumRf05Seeder;
use Database\Seeders\SectionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PremiumRf05SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_all_pdf_questions_scores_figures_and_premium_tryout(): void
    {
        Storage::fake('local');
        $this->seed(SectionSeeder::class);
        $package = Package::factory()->create(['name' => 'Paket 3']);

        $this->seed(PremiumRf05Seeder::class);

        $this->assertDatabaseCount('questions', 110);
        $this->assertSame(30, Question::whereHas('section', fn ($query) => $query->where('code', 'TWK'))->count());
        $this->assertSame(35, Question::whereHas('section', fn ($query) => $query->where('code', 'TIU'))->count());
        $this->assertSame(45, Question::where('scoring_type', QuestionScoring::Weighted)->count());
        $this->assertSame(3, Question::where('source_key', 'rf05-premium-001')->sole()->correct_option);
        $this->assertSame([3, 1, 5, 2, 4], Question::where('source_key', 'rf05-premium-066')->sole()->option_scores);
        $this->assertSame([3, 4, 1, 5, 2], Question::where('source_key', 'rf05-premium-086')->sole()->option_scores);
        foreach (Question::where('scoring_type', QuestionScoring::Weighted)->get() as $question) {
            $this->assertNull($question->correct_option);
            $this->assertSame(5, count($question->options));
            $scores = $question->option_scores;
            sort($scores);
            $this->assertSame([1, 2, 3, 4, 5], $scores);
        }
        $figures = Question::whereNotNull('image_path')->get();
        $this->assertCount(7, $figures);
        foreach ($figures as $figure) {
            Storage::disk('local')->assertExists($figure->image_path);
            $this->assertFalse($figure->shuffle_options);
        }
        $exam = Exam::where('slug', 'rf-05-premium')->sole();
        $this->assertSame(110, $exam->questions()->count());
        $this->assertSame(550, $exam->maximumScore());
        $this->assertSame([$exam->id], $package->exams()->pluck('exams.id')->all());
        $attempt = ExamAttempt::resumeOrStart(User::factory()->admin()->create(), $exam);
        $answers = [];
        foreach ($attempt->question_snapshot['questions'] as $question) {
            $answers[$question['id']] = $question['scoringType'] === 'weighted'
                ? array_search(5, $question['optionScores'], true) : $question['answer'];
        }
        $attempt->update(['answers' => $answers]);

        $attempt->complete();

        $this->assertSame([550, 550, 65, 0, 45], [$attempt->score, $attempt->max_score, $attempt->correct_count,
            $attempt->wrong_count, $attempt->weighted_answered_count]);
    }

    public function test_reseeding_preserves_renamed_edited_and_archived_imported_questions(): void
    {
        Storage::fake('local');
        $this->seed(SectionSeeder::class);
        $this->seed(PremiumRf05Seeder::class);
        $question = Question::where('source_key', 'rf05-premium-066')->sole();
        $question->update(['title' => 'Soal TKP versi admin', 'option_scores' => [5, 4, 3, 2, 1]]);
        $archived = Question::where('source_key', 'rf05-premium-067')->sole();
        $archived->delete();
        $exam = Exam::where('slug', 'rf-05-premium')->sole();
        $exam->update(['duration_minutes' => 120]);

        $this->seed(PremiumRf05Seeder::class);

        $this->assertSame(110, Question::withTrashed()->count());
        $this->assertSame('Soal TKP versi admin', $question->fresh()->title);
        $this->assertSame([5, 4, 3, 2, 1], $question->fresh()->option_scores);
        $this->assertSoftDeleted($archived);
        $this->assertSame(1, Exam::withTrashed()->count());
        $this->assertSame(120, $exam->fresh()->duration_minutes);
    }

    public function test_import_skips_missing_or_inactive_skd_sections(): void
    {
        Section::factory()->create(['code' => 'TWK']);
        Section::factory()->create(['code' => 'TIU']);
        Section::factory()->inactive()->create(['code' => 'TKP']);

        $this->seed(PremiumRf05Seeder::class);

        $this->assertDatabaseCount('questions', 0);
        $this->assertDatabaseCount('exams', 0);
    }
}
