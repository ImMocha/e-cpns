<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Format materi.
 *
 * Dua format ini yang ada di scope invoice (§10.1 PDF dan §10.2 Video).
 * Artikel punya modul sendiri (§10.3), bukan enum di sini.
 *
 * Kolom sumber pada tabel `materials` sengaja dibuat netral: PDF memakai
 * `file_path`, video memakai `video_url`. Sumber video masih TBD (§44),
 * jadi tidak ada yang dikunci di sini.
 */
enum MaterialType: string
{
    case Pdf = 'pdf';
    case Video = 'video';

    public function label(): string
    {
        return match ($this) {
            self::Pdf => 'Materi PDF',
            self::Video => 'Video Pembelajaran',
        };
    }

    /** Label pendek untuk kolom tabel. */
    public function shortLabel(): string
    {
        return match ($this) {
            self::Pdf => 'PDF',
            self::Video => 'Video',
        };
    }

    /** Class badge yang tersedia di components.css. */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Pdf => 'badge--primary',
            self::Video => 'badge--pink',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pdf => 'file',
            self::Video => 'play-circle',
        };
    }

    /** @return array<int, string> */
    public static function options(): array
    {
        return array_column(self::cases(), 'value');
    }
}
