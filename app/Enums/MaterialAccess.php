<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Level akses materi, mengikuti §38 Access Control.
 *
 * `premium` berarti peserta harus punya entitlement paket yang valid dulu.
 * Menentukan paket mana yang memenuhi setiap materi ikut TBD (§44 TBD-08),
 * jadi untuk sekarang `premium` cukup berarti "butuh paket".
 */
enum MaterialAccess: string
{
    case Free = 'free';
    case Premium = 'premium';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Gratis',
            self::Premium => 'Premium',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Free => 'badge--mint',
            self::Premium => 'badge--amber',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Free => 'unlock',
            self::Premium => 'lock',
        };
    }

    /** @return array<int, string> */
    public static function options(): array
    {
        return array_column(self::cases(), 'value');
    }
}
