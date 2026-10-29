<?php

declare(strict_types=1);

namespace App\Enums;

enum PackageTier: string
{
    case Regular = 'regular';
    case Bundling = 'bundling';
    case Premium = 'premium';

    public function label(): string
    {
        return match ($this) {
            self::Regular => 'Paket Reguler',
            self::Bundling => 'Paket Bundling',
            self::Premium => 'Paket Premium',
        };
    }

    public function tryoutCount(): ?int
    {
        return match ($this) {
            self::Regular => 1,
            self::Bundling => 3,
            self::Premium => null,
        };
    }

    public function attemptCount(): ?int
    {
        return match ($this) {
            self::Regular => 2,
            self::Bundling => 3,
            self::Premium => null,
        };
    }

    public function leaderboardAccess(): LeaderboardAccess
    {
        return match ($this) {
            self::Regular => LeaderboardAccess::None,
            self::Bundling => LeaderboardAccess::Local,
            self::Premium => LeaderboardAccess::National,
        };
    }

    /** @return list<string> */
    public function benefits(): array
    {
        return $this->configuredBenefits($this->tryoutCount() ?? 0, $this->attemptCount(), $this->leaderboardAccess());
    }

    /** @return list<string> */
    public function configuredBenefits(int $tokens, ?int $quota, LeaderboardAccess $leaderboard): array
    {
        $benefits = [
            $this === self::Premium ? 'Akses paket soal Tryout SKD unlimited' : $tokens.' token pilihan paket soal Tryout SKD',
            $quota === null ? 'Akses percobaan unlimited' : 'Dapat akses '.$quota.' x percobaan total',
            'Kunci jawaban dan pembahasan',
        ];
        if ($this === self::Premium) {
            array_push($benefits, 'Materi Lengkap SKD', 'Latihan soal SKD', 'Progress belajar (analisa pembelajaran)', 'Statistik nilai');
        }
        if ($leaderboard !== LeaderboardAccess::None) {
            $benefits[] = $leaderboard === LeaderboardAccess::National ? 'Perangkingan Nasional' : 'Perangkingan Daerah';
        }
        if ($this === self::Premium) {
            $benefits[] = 'Sertifikat';
        }

        return $benefits;
    }
}
