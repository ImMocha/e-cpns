<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\MaterialAccess;
use App\Enums\QuestionDifficulty;
use App\Enums\QuestionScoring;
use App\Enums\QuestionStatus;
use App\Models\Exam;
use App\Models\Package;
use App\Models\Question;
use App\Models\Section;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PremiumRf05Seeder extends Seeder
{
    public function run(): void
    {
        $directory = database_path('seeders/data/rf05-premium');
        $data = json_decode(file_get_contents($directory.'/questions.json'), true, flags: JSON_THROW_ON_ERROR);
        $sections = Section::query()->active()->whereIn('code', ['TWK', 'TIU', 'TKP'])->get()->keyBy('code');
        if ($sections->count() !== 3) {
            $this->command?->warn('RF 05 belum disemai. Siapkan bidang aktif TWK, TIU, dan TKP terlebih dahulu.');

            return;
        }

        $questionIds = [];
        foreach ($data['questions'] as $definition) {
            $question = DB::transaction(function () use ($definition, $directory, $sections): Question {
                $figurePath = $definition['figure'] ? 'questions/images/rf05-premium/'.$definition['figure'] : null;
                $question = Question::withTrashed()->firstOrCreate(['source_key' => $definition['source_key']], [
                    'section_id' => $sections->get($definition['section_code'])->id,
                    'title' => $definition['title'],
                    'body' => $definition['body'],
                    'options' => $definition['options'],
                    'correct_option' => $definition['correct_option'],
                    'scoring_type' => $definition['option_scores'] === null ? QuestionScoring::Binary : QuestionScoring::Weighted,
                    'option_scores' => $definition['option_scores'],
                    'shuffle_options' => $definition['figure'] === null,
                    'image_path' => $figurePath,
                    'explanation' => $definition['explanation'],
                    'difficulty' => QuestionDifficulty::Medium,
                    'status' => QuestionStatus::Published,
                    'published_at' => now(),
                ]);

                if ($question->wasRecentlyCreated && $figurePath !== null
                    && ! Storage::disk('local')->put($figurePath, file_get_contents($directory.'/'.$definition['figure']))) {
                    throw new RuntimeException('Gambar soal RF 05 gagal disimpan.');
                }

                return $question;
            });

            if (Question::query()->visibleToMembers()->whereKey($question->id)->exists()) {
                $questionIds[] = $question->id;
            }
        }

        if (count($questionIds) !== 110) {
            $this->command?->warn('Tryout RF 05 belum dibuat karena beberapa soal diarsipkan atau tidak terbit.');

            return;
        }

        DB::transaction(function () use ($questionIds): void {
            $exam = Exam::withTrashed()->firstOrCreate(['slug' => 'rf-05-premium'], [
                'kind' => 'tryout', 'title' => 'RF 05 Premium — Simulasi SKD',
                'description' => '30 soal TWK, 35 soal TIU, dan 45 soal TKP berbobot. Skor TKP mengikuti nilai 1–5 per pilihan.',
                'question_count' => 110, 'duration_minutes' => 100,
                'points_per_correct' => 5, 'penalty_wrong' => 0,
                'random_questions' => false, 'random_options' => true,
                'status' => QuestionStatus::Published, 'access_level' => MaterialAccess::Premium,
            ]);
            if ($exam->wasRecentlyCreated) {
                $exam->questions()->sync($questionIds);
                $package = Package::query()->where('name', 'Paket 3')->first();
                $package?->exams()->syncWithoutDetaching([$exam->id]);
            }
        });
    }
}
