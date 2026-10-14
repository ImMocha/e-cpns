<?php

declare(strict_types=1);

namespace App\Enums;

enum LeaderboardAccess: string
{
    case None = 'none';
    case Local = 'local';
    case National = 'national';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Tanpa leaderboard',
            self::Local => 'Leaderboard lokal',
            self::National => 'Leaderboard lokal + nasional',
        };
    }
}
