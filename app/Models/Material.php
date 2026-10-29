<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MaterialAccess;
use App\Enums\MaterialStatus;
use App\Enums\MaterialType;
use App\UniqueSlug;
use Database\Factories\MaterialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * Materi pembelajaran: PDF atau video, milik satu bidang (§33).
 *
 * Kolom sumber dipisah per format (`file_path` vs `video_url`) supaya aturan
 * validasinya jelas.
 */
#[Fillable([
    'section_id',
    'topic_id',
    'title',
    'slug',
    'summary',
    'type',
    'access_level',
    'status',
    'file_path',
    'file_size',
    'page_count',
    'video_url',
    'duration_minutes',
    'position',
    'published_at',
    'created_by',
    'updated_by',
])]
class Material extends Model
{
    /** @use HasFactory<MaterialFactory> */
    use HasFactory, SoftDeletes;

    /** Batas ukuran PDF dari §10.1 (20 MB). */
    public const MAX_PDF_KILOBYTES = 20480;

    /**
     * Disk tempat PDF disimpan.
     *
     * `local` = `storage/app/private`, **bukan** `public`. Berkas materi tidak
     * boleh bisa diakses langsung lewat URL; semua pembacaan lewat
     * `materials.file` yang mengecek akses lebih dulu.
     */
    public const PDF_DISK = 'local';

    /** Folder penyimpanan di dalam disk. */
    public const PDF_DIRECTORY = 'materials/pdf';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $attributes = ['access_level' => 'premium'];

    protected function casts(): array
    {
        return [
            'type' => MaterialType::class,
            'access_level' => MaterialAccess::class,
            'status' => MaterialStatus::class,
            'file_size' => 'integer',
            'page_count' => 'integer',
            'duration_minutes' => 'integer',
            'position' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    // ---- Relations -----------------------------------------------------------

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function progressRecords(): HasMany
    {
        return $this->hasMany(MaterialProgress::class);
    }

    // ---- Scopes ---------------------------------------------------------------

    /** @param  Builder<Material>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', MaterialStatus::Published->value);
    }

    /**
     * @param  Builder<Material>  $query
     * @return Builder<Material>
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $inner) use ($term) {
            $inner->where('title', 'like', "%{$term}%")
                ->orWhere('summary', 'like', "%{$term}%");
        });
    }

    /**
     * @param  Builder<Material>  $query
     * @return Builder<Material>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('section_id')->orderBy('position')->orderBy('title');
    }

    // ---- Slug -----------------------------------------------------------------

    /** Slug unik dari judul; `$ignoreId` dipakai saat update. */
    public static function makeSlug(string $title, ?int $ignoreId = null): string
    {
        return UniqueSlug::resolve('materials', $title, $ignoreId);
    }

    /** Isi slug bila kosong, lalu pastikan unik. Dipanggil dari controller. */
    public static function resolveSlug(?string $slug, string $title, ?int $ignoreId = null): string
    {
        $candidate = trim((string) $slug);

        return static::makeSlug($candidate !== '' ? $candidate : $title, $ignoreId);
    }

    // ---- Presentation ---------------------------------------------------------

    public function isPublished(): bool
    {
        return $this->status === MaterialStatus::Published;
    }

    /**
     * Apakah materi sudah punya sumber yang benar-benar bisa dibuka.
     *
     * Dipakai aksi terbit-kan: materi tanpa sumber tidak boleh tayang, karena
     * peserta akan melihat kartu yang tidak bisa diklik apa pun.
     */
    public function hasContentSource(): bool
    {
        return $this->type === MaterialType::Video
            ? filled($this->video_url)
            : filled($this->file_path);
    }

    /**
     * URL berkas PDF.
     *
     * Sengaja bukan `Storage::url()`: berkas disimpan di disk privat dan hanya bisa
     * diambil lewat route `materials.file` yang mengecek akses. Kalau PDF berada di
     * `public/`, siapa pun bisa membacanya hanya dengan menebak URL — gating
     * premium jadi tidak berarti apa-apa.
     */
    public function fileUrl(): ?string
    {
        if ($this->file_path === null || $this->file_path === '') {
            return null;
        }

        return route('materials.file', $this);
    }

    /**
     * Apakah berkas PDF-nya benar-benar ada di disk.
     *
     * `file_path` yang terisi tidak menjamin berkasnya ada — seed lama, restore
     * database, atau unggahan yang gagal bisa meninggalkan path yang menunjuk ke
     * file yang tidak ada. Kalau tidak dicek, peserta melihat frame PDF yang 404
     * tanpa penjelasan apa pun.
     */
    public function fileExists(): bool
    {
        return $this->file_path !== null
            && $this->file_path !== ''
            && Storage::disk(self::PDF_DISK)->exists($this->file_path);
    }

    public function hasAvailableContent(): bool
    {
        return $this->hasContentSource()
            && ($this->type === MaterialType::Video || $this->fileExists());
    }

    /** Ukuran berkas dalam format Indonesia, mis. `2,4 MB`. */
    public function humanFileSize(): ?string
    {
        if ($this->file_size === null) {
            return null;
        }

        $bytes = max((int) $this->file_size, 0);
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $power = min($power, count($units) - 1);

        $value = $bytes / (1024 ** $power);

        return number_format($value, $power === 0 ? 0 : 1, ',', '.').' '.$units[$power];
    }

    /**
     * Durasi video dalam bahasa manusia, mis. `24 menit`.
     */
    public function humanDuration(): ?string
    {
        if ($this->duration_minutes === null) {
            return null;
        }

        return $this->duration_minutes.' menit';
    }

    // ---- Access control (§38) -------------------------------------------------

    /**
     * Apakah materi boleh dibuka oleh pengguna tertentu.
     *
     * Ini satu-satunya tempat yang memutuskan akses, supaya route, query, dan
     * Blade tidak punya logika berbeda. Aturannya:
     *
     * 1. Admin boleh membuka semuanya — termasuk draft — untuk keperluan
     *    pemeriksaan sebelum diterbitkan.
     * 2. `free` boleh dibuka siapa pun yang sudah login.
     * 3. `premium` butuh paket aktif yang memuat materi ini.
     */
    public function isAccessibleBy(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->canAccessLearningFeatures()) {
            return false;
        }

        return $user->hasPremiumPackage() || $this->access_level === MaterialAccess::Free
            || $this->hasActivePackage($user);
    }

    /**
     * Pesan singkat untuk ditampilkan saat akses ditolak.
     *
     * Bedanya penting: premium ditolak karena belum ada paket, sedangkan materi
     * tanpa sumber ditolak karena salah diisi admin. Keduanya memang tidak bisa
     * dibuka, tapi tindakan yang diminta peserta berbeda.
     */
    public function accessNotice(?User $user): ?string
    {
        if ($this->isAccessibleBy($user)) {
            return null;
        }

        if (! $this->hasContentSource()) {
            return 'Materi ini belum punya berkas atau tautan yang bisa dibuka. Hubungi admin Ganesha Nusa.';
        }

        return 'Materi ini bagian dari paket Premium. Pilih paket belajar untuk membukanya.';
    }

    /** Akses mengikuti isi paket saat aktivasi, bukan katalog paket saat ini. */
    public function hasActivePackage(User $user): bool
    {
        if ($user->hasPremiumPackage()) {
            return true;
        }
        if (! $user->canAccessLearningFeatures()) {
            return false;
        }

        if ($this->relationLoaded('userPackages')) {
            return $this->userPackages->contains(fn (UserPackage $grant): bool => $grant->user_id === $user->id && $grant->isActive());
        }

        return $this->userPackages()->active()->whereBelongsTo($user)->exists();
    }

    public function userPackages(): BelongsToMany
    {
        return $this->belongsToMany(UserPackage::class);
    }

    /** @param Builder<Material> $query */
    public function scopeAccessibleTo(Builder $query, User $user): void
    {
        if ($user->hasPremiumPackage()) {
            return;
        }
        if (! $user->canAccessLearningFeatures()) {
            $query->whereRaw('1 = 0');

            return;
        }
        if (! $user->isAdmin()) {
            $query->where(fn (Builder $access) => $access->where('access_level', MaterialAccess::Free)
                ->orWhereHas('userPackages', fn (Builder $grants) => $grants->active()->whereBelongsTo($user)));
        }
    }

    /**
     * Materi yang boleh dilihat peserta: sudah terbit, owning section-nya masih
     * aktif, dan soft delete tidak dipakai.
     *
     * Dipakai juga untuk query daftar supaya tidak ada memfilter ulang di Blade.
     *
     * @param  Builder<Material>  $query
     * @return Builder<Material>
     */
    public function scopeVisibleToMembers(Builder $query): Builder
    {
        return $query->published()->inActiveTopics();
    }

    /** @param Builder<Material> $query */
    public function scopeInActiveTopics(Builder $query): Builder
    {
        return $query->whereHas('section', fn (Builder $section) => $section->active())
            ->whereHas('topic', fn (Builder $topic) => $topic->active()
                ->whereColumn('topics.section_id', 'materials.section_id'))
            ->with(['section', 'topic']);
    }

    /**
     * Padanan satu-model dari `scopeVisibleToMembers()`.
     *
     * Sengaja memanggil scope-nya sendiri, bukan menyalin kondisinya: kalau
     * aturan visibility berubah, daftar dan halaman baca ikut berubah di tempat
     * yang sama dan tidak bisa melenceng.
     */
    public function isVisibleToMember(): bool
    {
        return static::query()
            ->visibleToMembers()
            ->whereKey($this->getKey())
            ->exists();
    }
}
