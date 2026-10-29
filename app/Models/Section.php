<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ColorTone;
use App\UniqueSlug;
use Database\Factories\SectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name',
    'slug',
    'code',
    'description',
    'position',
    'is_active',
    'icon',
    'tone',
    'created_by',
    'updated_by',
])]
class Section extends Model
{
    /** @use HasFactory<SectionFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Ikon yang boleh dipilih admin. Satu sumber untuk dropdown di form
     * dan aturan validasi, supaya tidak ada daftar ganda.
     *
     * Hanya berisi nama yang benar-benar ada di resources/js/lontar/icons.js.
     * Test `SectionIconsExistInIconSet` menjaga daftarnya tetap sinkron.
     *
     * @var array<int, string>
     */
    public const ICONS = [
        'book', 'layers', 'chart', 'shield', 'folder', 'file',
        'pen-line', 'list-checks', 'clipboard', 'target', 'gauge',
        'megaphone', 'quote', 'award', 'trophy', 'crown',
        'zap', 'monitor', 'calendar', 'grid', 'send', 'key',
    ];

    /** @return array<int, string> */
    public static function iconOptions(): array
    {
        return self::ICONS;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    // ---- Relations -----------------------------------------------------------

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class);
    }

    // ---- Scopes ---------------------------------------------------------------

    /** @param  Builder<Section>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param  Builder<Section>  $query */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $inner) use ($term) {
            $inner->where('name', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }

    /**
     * Urutan tampil: urutan manual dulu, lalu nama sebagai penentu agar stabil.
     *
     * @param  Builder<Section>  $query
     * @return Builder<Section>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('name');
    }

    // ---- Slug -----------------------------------------------------------------

    /**
     * Slug unik dari nama.
     *
     * `$ignoreId` dipakai saat update supaya bagian tidak bentrok dengan
     * slug miliknya sendiri.
     */
    public static function makeSlug(string $name, ?int $ignoreId = null): string
    {
        return UniqueSlug::resolve('sections', $name, $ignoreId);
    }

    /** Isi slug bila kosong, lalu pastikan unik. Dipanggil dari controller. */
    public static function resolveSlug(?string $slug, string $name, ?int $ignoreId = null): string
    {
        $candidate = trim((string) $slug);

        return static::makeSlug($candidate !== '' ? $candidate : $name, $ignoreId);
    }

    // ---- Presentation ---------------------------------------------------------

    /** Warna yang benar-benar dipakai: kolom `tone`, atau tebakan dari urutan. */
    public function resolvedTone(): ColorTone
    {
        $tone = ColorTone::tryFrom((string) $this->tone);

        if ($tone !== null) {
            return $tone;
        }

        // Belum di-set admin: sebar warna sesuai posisi supaya tidak semua sama.
        $tones = ColorTone::cases();

        return $tones[($this->position ?? 0) % count($tones)];
    }

    /** Ikon yang dipakai kalau kolom `icon` kosong. */
    public function resolvedIcon(): string
    {
        $icon = (string) $this->icon;

        return in_array($icon, self::iconOptions(), true)
            ? $icon
            : self::iconOptions()[($this->position ?? 0) % count(self::iconOptions())];
    }

    /**
     * Saran kode dari nama, mis. "Bahasa Indonesia" -> "BAH".
     *
     * Aturannya: huruf/angka dari nama, ambil 3 karakter pertama, uppercase.
     * Admin tetap boleh mengetik sendiri; saran ini hanya mengisi kolom
     * selama belum disentuh.
     */
    public static function suggestCode(string $name): string
    {
        $letters = preg_replace('/[^A-Za-z0-9]+/', '', $name) ?? '';
        $suffix = strtoupper(substr($letters, 0, 3));

        return $suffix !== '' ? $suffix : 'BID';
    }
}
