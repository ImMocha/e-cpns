<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\LeaderboardAccess;
use App\Enums\MaterialAccess;
use App\Models\Exam;
use App\Models\Material;
use App\Models\Package;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LearningPackageSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PremiumTryoutSeeder::class);

        $tryouts = Exam::query()->published()->where('kind', 'tryout')
            ->where('access_level', MaterialAccess::Premium)
            ->whereIn('slug', array_values(PremiumTryoutSeeder::SLUGS))
            ->withCount(['questions as available_questions_count' => fn ($query) => $query->visibleToMembers()])
            ->get()->filter(fn (Exam $exam): bool => $exam->available_questions_count >= $exam->question_count)
            ->keyBy('slug');

        $firstTryout = $tryouts->get(PremiumTryoutSeeder::SLUGS['TWK']);
        if ($firstTryout !== null) {
            $this->createPackage('Paket 1', [
                'description' => '1 paket soal tryout SKD TWK, 2 percobaan total, kunci jawaban dan pembahasan.',
                'price' => 15000,
                'duration_days' => 365,
                'tryout_quota' => 2,
                'leaderboard_access' => LeaderboardAccess::None,
                'position' => 1,
            ], [$firstTryout->id], []);
        }

        if ($tryouts->count() !== 3) {
            $this->command?->warn('Paket 2 dan 3 belum dibuat karena bundel 3 tryout SKD belum lengkap.');

            return;
        }

        $tryoutIds = collect(PremiumTryoutSeeder::SLUGS)
            ->map(fn (string $slug): int => $tryouts->get($slug)->id)->all();

        $this->createPackage('Paket 2', [
            'description' => 'Bundling 3 paket soal tryout SKD TWK, TIU, dan TKP; 3 percobaan total, pembahasan, dan leaderboard daerah.',
            'price' => 35000,
            'duration_days' => 365,
            'tryout_quota' => 3,
            'leaderboard_access' => LeaderboardAccess::Local,
            'position' => 2,
        ], $tryoutIds, []);

        $materialIds = Material::query()->visibleToMembers()
            ->where('access_level', MaterialAccess::Premium)->pluck('materials.id')->all();
        if ($materialIds === []) {
            $this->command?->warn('Paket 3 belum dibuat karena materi SKD premium belum tersedia.');

            return;
        }

        $premiumExamIds = Exam::query()->published()->where('access_level', MaterialAccess::Premium)
            ->where('slug', '!=', 'contoh-tryout-premium-paket-uji')->pluck('exams.id')->all();
        $quizIds = Exam::query()->visibleToMembers()->where('kind', 'quiz')->pluck('exams.id')->all();

        $this->createPackage('Paket 3', [
            'description' => 'Seluruh konten premium SKD yang tersedia, latihan soal, tryout tanpa batas, pembahasan, leaderboard nasional dan daerah, progres belajar, analisis pembelajaran, serta statistik nilai.',
            'price' => 80000,
            'duration_days' => 365,
            'tryout_quota' => null,
            'leaderboard_access' => LeaderboardAccess::National,
            'position' => 3,
        ], array_values(array_unique([...$premiumExamIds, ...$quizIds])), $materialIds);
    }

    /**
     * @param  array{description: string, price: int, duration_days: int, tryout_quota: ?int, leaderboard_access: LeaderboardAccess, position: int}  $attributes
     * @param  list<int>  $examIds
     * @param  list<int>  $materialIds
     */
    private function createPackage(string $name, array $attributes, array $examIds, array $materialIds): void
    {
        DB::transaction(function () use ($name, $attributes, $examIds, $materialIds): void {
            $package = Package::withTrashed()->firstOrCreate(['name' => $name], $attributes + ['is_active' => true]);
            if ($package->wasRecentlyCreated) {
                $package->exams()->sync($examIds);
                $package->materials()->sync($materialIds);
            }
        });
    }
}
