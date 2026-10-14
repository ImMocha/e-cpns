<?php

declare(strict_types=1);

namespace App\Enums;

enum QuestionScoring: string
{
    case Binary = 'binary';
    case Weighted = 'weighted';

    public function label(): string
    {
        return match ($this) {
            self::Binary => 'Benar / salah',
            self::Weighted => 'Skor per pilihan (TKP)',
        };
    }
}
