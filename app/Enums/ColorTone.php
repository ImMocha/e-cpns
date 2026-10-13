<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Warna dari design system LONTAR untuk kartu, badge, dan ikon.
 *
 * Nilai `value` cocok dengan modifier `.ico--*` di components.css dan
 * class badge yang tersedia. Tidak ada warna lain: menambah warna berarti
 * menambah token baru di tokens.css dulu.
 */
enum ColorTone: string
{
    case Primary = 'primary';
    case Pink = 'pink';
    case Amber = 'amber';
    case Mint = 'mint';
    case Violet = 'violet';
    case Coral = 'coral';
    case Ink = 'ink';

    public function label(): string
    {
        return match ($this) {
            self::Primary => 'Biru',
            self::Pink => 'Merah Muda',
            self::Amber => 'Kuning',
            self::Mint => 'Hijau Mint',
            self::Violet => 'Ungu',
            self::Coral => 'Oranye',
            self::Ink => 'Gelap',
        };
    }

    /** Modifier `.ico--*` untuk kartu / ikon. */
    public function iconModifier(): string
    {
        return 'ico--'.$this->value;
    }

    /** Class badge yang tersedia di components.css. */
    public function badgeClass(): string
    {
        return 'badge--'.$this->value;
    }

    /**
     * Modifier `.tile__media--*` untuk kartu materi.
     *
     * String kosong berarti warna dasar `.tile__media` sudah cocok, jadi tidak
     * perlu modifier tambahan.
     */
    public function mediaModifier(): string
    {
        return match ($this) {
            self::Primary, self::Coral, self::Ink => '',
            default => 'tile__media--'.$this->value,
        };
    }

    /** Tinta yang kontras di atas warna ini; null berarti pakai putih. */
    public function foreground(): ?string
    {
        return match ($this) {
            self::Amber, self::Mint => 'var(--ink)',
            default => '#fff',
        };
    }

    /** CSS custom property untuk latar. */
    public function backgroundVar(): string
    {
        return match ($this) {
            self::Primary => 'var(--primary)',
            self::Ink => 'var(--ink)',
            default => 'var(--pop-'.$this->value.')',
        };
    }

    /** @return array<int, string> */
    public static function options(): array
    {
        return array_column(self::cases(), 'value');
    }
}
