<?php

declare(strict_types=1);

namespace App;

use App\Enums\Province;
use App\Models\LeaderboardBot;
use Illuminate\Support\Facades\DB;
use LogicException;

class LeaderboardSimulation
{
    private const int PROFILE_VERSION = 2;

    public function enabled(): bool
    {
        return (bool) DB::table('leaderboard_settings')->where('id', 1)->value('simulations_enabled');
    }

    /** @return array{minimum: int, maximum: int} */
    public function populationRange(): array
    {
        $settings = DB::table('leaderboard_settings')->where('id', 1)->first();

        return ['minimum' => (int) ($settings?->minimum_per_province ?? 15), 'maximum' => (int) ($settings?->maximum_per_province ?? 25)];
    }

    public function setEnabled(bool $enabled, ?int $minimum = null, ?int $maximum = null): void
    {
        $range = $this->populationRange();
        $minimum ??= $range['minimum'];
        $maximum ??= $range['maximum'];

        DB::transaction(function () use ($enabled, $minimum, $maximum, $range): void {
            DB::table('leaderboard_settings')->updateOrInsert(['id' => 1], [
                'simulations_enabled' => $enabled,
                'minimum_per_province' => $minimum,
                'maximum_per_province' => $maximum,
            ]);

            if ($enabled || $minimum !== $range['minimum'] || $maximum !== $range['maximum']) {
                $this->seedMissing();
            }
        });
    }

    public function seedMissing(): void
    {
        $range = $this->populationRange();
        $existingProfiles = LeaderboardBot::query()->orderBy('id')->get();
        $usedNames = array_fill_keys($existingProfiles->pluck('name')->all(), true);
        $usedScores = $existingProfiles->map(fn (LeaderboardBot $profile): int => (int) round((float) $profile->score * 100))->all();
        $availableScores = array_values(array_diff(range(4200, 9700), $usedScores));
        shuffle($availableScores);

        foreach (Province::cases() as $province) {
            $profiles = $existingProfiles->filter(fn (LeaderboardBot $profile): bool => $profile->province === $province)->values();

            foreach ($profiles as $profile) {
                if ($profile->generation_version < self::PROFILE_VERSION && preg_match('/\Astarter-'.$province->name.'-[1-9][0-9]*\z/', $profile->source_key) === 1) {
                    $profile->update($this->generateProfile($availableScores, $usedNames));
                }
            }

            $visibleCount = $profiles->where('is_visible', true)->count();

            if ($visibleCount >= $range['minimum'] && $visibleCount <= $range['maximum']) {
                continue;
            }

            $target = random_int($range['minimum'], $range['maximum']);
            $existingKeys = $profiles->pluck('source_key')->all();

            for ($place = 1; $profiles->count() < $target; $place++) {
                $key = 'starter-'.$province->name.'-'.$place;
                if (in_array($key, $existingKeys, true)) {
                    continue;
                }

                $profiles->push(LeaderboardBot::query()->firstOrCreate(
                    ['source_key' => $key],
                    ['province' => $province] + $this->generateProfile($availableScores, $usedNames),
                ));
            }

            $visibleIds = $profiles->take($target)->pluck('id')->all();
            LeaderboardBot::query()->where('province', $province->value)->whereNotIn('id', $visibleIds)->update(['is_visible' => false]);
            LeaderboardBot::query()->whereIn('id', $visibleIds)->update(['is_visible' => true]);
        }
    }

    /**
     * @param  list<int>  $availableScores
     * @param  array<string, true>  $usedNames
     * @return array{name: string, score: float, generation_version: int}
     */
    private function generateProfile(array &$availableScores, array &$usedNames): array
    {
        $score = array_pop($availableScores);
        if ($score === null) {
            throw new LogicException('Tidak ada nilai contoh unik yang tersedia untuk peserta virtual.');
        }

        $maleFirstNames = ['Aditya', 'Agus', 'Akbar', 'Andi', 'Arif', 'Bagas', 'Bambang', 'Bayu', 'Dimas', 'Eko', 'Fajar', 'Farhan', 'Gilang', 'Hendra', 'Ilham', 'Irwan', 'Kevin', 'Raka', 'Reza', 'Rizky', 'Surya', 'Taufik', 'Wahyu', 'Yoga'];
        $femaleFirstNames = ['Amelia', 'Anisa', 'Aulia', 'Ayu', 'Citra', 'Dewi', 'Dinda', 'Dita', 'Fitri', 'Hana', 'Indah', 'Intan', 'Kartika', 'Laras', 'Maya', 'Nabila', 'Nadia', 'Nisa', 'Putri', 'Rani', 'Ratna', 'Salsabila', 'Tiara', 'Wulan'];
        $maleLastNames = ['Pratama', 'Nugraha', 'Saputra', 'Firmansyah', 'Wibowo', 'Permana', 'Ramadhan', 'Wijaya', 'Setiawan', 'Hidayat', 'Kurniawan', 'Maulana', 'Hakim', 'Pangestu', 'Santoso', 'Wicaksono', 'Prasetyo', 'Kusuma', 'Gunawan', 'Syahputra'];
        $femaleLastNames = ['Safitri', 'Lestari', 'Maharani', 'Permata', 'Ramadhani', 'Anggraini', 'Puspita', 'Sari', 'Wulandari', 'Azzahra', 'Utami', 'Oktaviani', 'Handayani', 'Rahmawati', 'Kusumawati', 'Novitasari', 'Febrianti', 'Melati', 'Anjani', 'Cahyani'];
        $middleNames = ['Cahya', 'Dwi', 'Eka', 'Nur', 'Rizki', 'Tri', 'Aulia', 'Febri', 'April', 'Putra', 'Putri', 'Dian'];

        do {
            $isMale = random_int(0, 1) === 1;
            $firstNames = $isMale ? $maleFirstNames : $femaleFirstNames;
            $lastNames = $isMale ? $maleLastNames : $femaleLastNames;
            $parts = [$firstNames[array_rand($firstNames)]];
            if (random_int(0, 2) === 0) {
                $parts[] = $middleNames[array_rand($middleNames)];
            }
            $parts[] = $lastNames[array_rand($lastNames)];
            $name = implode(' ', $parts);
        } while (isset($usedNames[$name]));

        $usedNames[$name] = true;

        return ['name' => $name, 'score' => $score / 100, 'generation_version' => self::PROFILE_VERSION];
    }
}
