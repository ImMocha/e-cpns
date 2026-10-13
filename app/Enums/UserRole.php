<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::User => 'Peserta',
        };
    }

    /** @return array<int, string> */
    public static function options(): array
    {
        return array_column(self::cases(), 'value');
    }
}
