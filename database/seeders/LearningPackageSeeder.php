<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\LeaderboardAccess;
use App\Enums\MaterialAccess;
use App\Enums\PackageTier;
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

        $this->createPackage('Paket Reguler', [
            'tier' => PackageTier::Regular,
            'description' => '1 token untuk membuka 1 paket soal tryout SKD pilihanmu, 2 percobaan total, kunci jawaban dan pembahasan.',
            'price' => 15000,
            'duration_days' => 365,
            'tryout_quota' => 2,
            'tryout_tokens' => 1,
            'leaderboard_access' => LeaderboardAccess::None,
            'position' => 1,
        ], [], []);

        $this->createPackage('Paket Bundling', [
            'tier' => PackageTier::Bundling,
            'description' => '3 token untuk membuka 3 paket soal tryout SKD pilihanmu, 3 percobaan total, pembahasan, dan perangkingan daerah.',
            'price' => 35000,
            'duration_days' => 365,
            'tryout_quota' => 3,
            'tryout_tokens' => 3,
            'leaderboard_access' => LeaderboardAccess::Local,
            'position' => 2,
        ], [], []);

        if ($tryouts->count() !== 3) {
            $this->command?->warn('Paket Premium belum dibuat karena konten tryout SKD belum lengkap.');

            return;
        }

        $materialIds = Material::query()->visibleToMembers()
            ->where('access_level', MaterialAccess::Premium)->pluck('materials.id')->all();
        if ($materialIds === []) {
            $this->command?->warn('Paket Premium belum dibuat karena materi SKD premium belum tersedia.');

            return;
        }

        $premiumExamIds = Exam::query()->published()->where('access_level', MaterialAccess::Premium)
            ->where('slug', '!=', 'contoh-tryout-premium-paket-uji')->pluck('exams.id')->all();
        $quizIds = Exam::query()->visibleToMembers()->where('kind', 'quiz')->pluck('exams.id')->all();

        $this->createPackage('Paket Premium', [
            'tier' => PackageTier::Premium,
            'description' => 'Seluruh konten premium SKD yang tersedia, latihan soal, tryout tanpa batas, pembahasan, leaderboard nasional dan daerah, progres belajar, analisis pembelajaran, statistik nilai, serta sertifikat.',
            'price' => 80000,
            'duration_days' => 365,
            'tryout_quota' => null,
            'leaderboard_access' => LeaderboardAccess::National,
            'position' => 3,
        ], array_values(array_unique([...$premiumExamIds, ...$quizIds])), $materialIds);
    }

    /**
     * @param  array{tier: PackageTier, description: string, price: int, duration_days: int, tryout_quota: ?int, tryout_tokens?: int, leaderboard_access: LeaderboardAccess, position: int}  $attributes
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
