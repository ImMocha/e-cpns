<?php

declare(strict_types=1);

namespace App\Enums;

enum QuestionDifficulty: string
{
    case Easy = 'easy';
    case Medium = 'medium';
    case Hard = 'hard';

    public function label(): string
    {
        return match ($this) {
            self::Easy => 'Mudah',
            self::Medium => 'Sedang',
            self::Hard => 'Sulit',
        };
    }
}
