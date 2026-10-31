<?php

declare(strict_types=1);

namespace App;

use App\Enums\Province;
use App\Models\LeaderboardBot;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class LeaderboardSimulation
{
    private const int PROFILE_VERSION = 3;

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

    /** @return array{national: int, local: int} */
    public function populationCounts(): array
    {
        $settings = DB::table('leaderboard_settings')->where('id', 1)->first();

        return ['national' => (int) ($settings?->national_bot_count ?? 5000), 'local' => (int) ($settings?->local_bot_count ?? 100)];
    }

    public function setCounts(bool $enabled, int $national, int $local): void
    {
        DB::transaction(function () use ($enabled, $national, $local): void {
            DB::table('leaderboard_settings')->updateOrInsert(['id' => 1], [
                'simulations_enabled' => $enabled, 'national_bot_count' => $national, 'local_bot_count' => $local,
            ]);
            $this->seedScopeCounts();
        });
    }

    public function bots(?Province $province = null): Builder
    {
        $query = LeaderboardBot::query()->where('is_visible', true);
        $settings = DB::table('leaderboard_settings')->where('id', 1)->first();
        if ($settings?->national_bot_count === null) {
            return $query->when($province !== null, fn ($query) => $query->where('province', $province->value));
        }
        $counts = $this->populationCounts();
        $ids = LeaderboardBot::query()->where('is_visible', true)
            ->when($province !== null, fn ($query) => $query->where('province', $province->value))
            ->select('id')->orderBy('id')->limit($province === null ? $counts['national'] : $counts['local']);

        return $query->whereIn('id', DB::query()->fromSub($ids, 'selected_bots')->select('id'));
    }

    private function seedScopeCounts(): void
    {
        $counts = $this->populationCounts();
        $provinces = Province::cases();
        $usedNames = array_fill_keys(LeaderboardBot::query()->pluck('name')->all(), true);
        foreach ($provinces as $index => $province) {
            $target = max($counts['local'], intdiv($counts['national'], count($provinces)) + ($index < $counts['national'] % count($provinces) ? 1 : 0));
            $profiles = LeaderboardBot::query()->where('province', $province->value)->orderBy('id')->get();
            for ($place = $profiles->count(); $place < $target; $place++) {
                $profiles->push(LeaderboardBot::query()->create([
                    'source_key' => 'scope-'.$province->name.'-'.bin2hex(random_bytes(8)),
                    'province' => $province,
                ] + $this->generateProfile($usedNames)));
            }
            $visibleIds = $profiles->take($target)->modelKeys();
            LeaderboardBot::query()->where('province', $province->value)->whereNotIn('id', $visibleIds)->where('is_visible', true)->update(['is_visible' => false]);
            LeaderboardBot::query()->whereIn('id', $visibleIds)->where('is_visible', false)->update(['is_visible' => true]);
        }
    }

    public function seedMissing(): void
    {
        if (DB::table('leaderboard_settings')->where('id', 1)->value('national_bot_count') !== null) {
            $this->seedScopeCounts();

            return;
        }
        $range = $this->populationRange();
        $existingProfiles = LeaderboardBot::query()->orderBy('id')->get();
        $usedNames = array_fill_keys($existingProfiles->pluck('name')->all(), true);

        foreach (Province::cases() as $province) {
            $profiles = $existingProfiles->filter(fn (LeaderboardBot $profile): bool => $profile->province === $province)->values();

            foreach ($profiles as $profile) {
                if ($profile->generation_version < self::PROFILE_VERSION) {
                    $score = (float) $profile->score;
                    $profile->update(['score' => (int) round($score <= 100 ? $score * 5.5 : $score),
                        'generation_version' => self::PROFILE_VERSION]);
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
                    ['province' => $province] + $this->generateProfile($usedNames),
                ));
            }

            $visibleIds = $profiles->take($target)->pluck('id')->all();
            LeaderboardBot::query()->where('province', $province->value)->whereNotIn('id', $visibleIds)->update(['is_visible' => false]);
            LeaderboardBot::query()->whereIn('id', $visibleIds)->update(['is_visible' => true]);
        }
    }

    /**
     * @param  array<string, true>  $usedNames
     * @return array{name: string, score: int, generation_version: int}
     */
    private function generateProfile(array &$usedNames): array
    {
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

        return ['name' => $name, 'score' => random_int(231, 534), 'generation_version' => self::PROFILE_VERSION];
    }
}
