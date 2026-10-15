<?php

declare(strict_types=1);

namespace App;

use App\Enums\Province;
use App\Models\LeaderboardBot;
use Illuminate\Support\Facades\DB;

class LeaderboardSimulation
{
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
        $firstNames = ['Aditya', 'Nadia', 'Fajar', 'Rizki', 'Dimas', 'Ayu', 'Arif', 'Nabila', 'Raka', 'Putri', 'Ilham', 'Salsabila', 'Yoga', 'Intan', 'Bayu', 'Dewi', 'Farhan', 'Tiara', 'Reza', 'Hana'];
        $middleNames = ['Budi', 'Cahya', 'Dwi', 'Eka', 'Gilang', 'Harum', 'Indra', 'Jaya', 'Kirana', 'Lintang', 'Mukti', 'Nur', 'Putra', 'Ratna', 'Setia', 'Tri', 'Utama', 'Wahyu', 'Yudha', 'Zahra'];
        $lastNames = ['Pratama', 'Safitri', 'Nugraha', 'Ramadhani', 'Saputra', 'Lestari', 'Firmansyah', 'Maharani', 'Wibowo', 'Permata'];

        foreach (Province::cases() as $provinceIndex => $province) {
            $profiles = LeaderboardBot::query()->where('province', $province->value)->orderBy('id')->get();
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

                $index = $provinceIndex * 100 + $place - 1;

                $profiles->push(LeaderboardBot::query()->firstOrCreate(
                    ['source_key' => $key],
                    [
                        'name' => $firstNames[$index % 20].' '.$middleNames[intdiv($index, 20) % 20].' '.$lastNames[intdiv($index, 400)],
                        'province' => $province,
                        'score' => 55 + ($index * 17) % 39,
                    ],
                ));
            }

            $visibleIds = $profiles->take($target)->pluck('id')->all();
            LeaderboardBot::query()->where('province', $province->value)->whereNotIn('id', $visibleIds)->update(['is_visible' => false]);
            LeaderboardBot::query()->whereIn('id', $visibleIds)->update(['is_visible' => true]);
        }
    }
}
