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
        return match ($this) {
            self::Regular => ['Akses satu paket soal Tryout SKD', 'Dapat akses 2 x percobaan', 'Kunci jawaban dan pembahasan'],
            self::Bundling => ['Akses tiga paket soal Tryout SKD', 'Dapat akses 3 x percobaan total', 'Kunci jawaban dan pembahasan', 'Perangkingan Daerah'],
            self::Premium => ['Akses paket soal Tryout SKD unlimited', 'Akses percobaan unlimited', 'Kunci jawaban dan pembahasan', 'Materi Lengkap SKD', 'Latihan soal SKD', 'Progress belajar (analisa pembelajaran)', 'Statistik nilai', 'Perangkingan Nasional', 'Sertifikat'],
        };
    }
}
