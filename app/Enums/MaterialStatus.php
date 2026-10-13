<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Status terpublikasi materi.
 *
 * `draft` tidak muncul di halaman peserta sama sekali. `published` berarti
 * materi sudah boleh dilihat — tapi masih bisa ditahan dengan cara dikembalikan
 * ke draft, karena "pernah terbit" bukan berarti "harus tetap terbit".
 */
enum MaterialStatus: string
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

    public function icon(): string
    {
        return match ($this) {
            self::Draft => 'pencil',
            self::Published => 'check',
        };
    }

    /** @return array<int, string> */
    public static function options(): array
    {
        return array_column(self::cases(), 'value');
    }
}
