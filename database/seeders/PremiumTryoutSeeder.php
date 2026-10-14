<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\MaterialAccess;
use App\Enums\QuestionStatus;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Section;
use Illuminate\Database\Seeder;

class PremiumTryoutSeeder extends Seeder
{
    public const SLUGS = [
        'TWK' => 'paket-soal-skd-twk',
        'TIU' => 'paket-soal-skd-tiu',
        'TKP' => 'paket-soal-skd-tkp',
    ];

    public function run(): void
    {
        foreach (self::SLUGS as $sectionCode => $slug) {
            if (Exam::withTrashed()->where('slug', $slug)->exists()) {
                continue;
            }

            $section = Section::query()->active()->where('code', $sectionCode)->first();
            if ($section === null) {
                continue;
            }

            $questionIds = Question::query()->visibleToMembers()->whereBelongsTo($section)
                ->orderBy('questions.id')->limit(3)->pluck('questions.id');
            if ($questionIds->count() !== 3) {
                $this->command?->warn("Tryout {$sectionCode} belum dibuat karena soal terbit kurang dari 3.");

                continue;
            }

            $exam = Exam::withTrashed()->firstOrCreate(['slug' => $slug], [
                'kind' => 'tryout',
                'title' => "Tryout SKD {$sectionCode}",
                'description' => "Paket soal {$sectionCode} untuk tryout SKD. Soal dan pengaturan dapat dikelola admin.",
                'status' => QuestionStatus::Published,
                'access_level' => MaterialAccess::Premium,
                'question_count' => 3,
                'duration_minutes' => 20,
                'points_per_correct' => 5,
                'penalty_wrong' => 0,
                'random_questions' => true,
                'random_options' => true,
            ]);

            if ($exam->wasRecentlyCreated) {
                $exam->questions()->sync($questionIds);
            }
        }
    }
}
