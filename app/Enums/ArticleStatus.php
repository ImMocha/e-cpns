<?php

declare(strict_types=1);

namespace App\Enums;

enum ArticleStatus: string
{
    case Draft = 'draft';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Terbit',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'badge--amber',
            self::Published => 'badge--mint',
        };
    }
}
