<?php

declare(strict_types=1);

namespace App\Enums;

enum QuestionStatus: string
{
    case Draft = 'draft';
    case Published = 'published';

    public function label(): string
    {
        return $this === self::Published ? 'Terbit' : 'Draft';
    }

    public function badgeClass(): string
    {
        return $this === self::Published ? 'badge--mint' : 'badge--amber';
    }
}
