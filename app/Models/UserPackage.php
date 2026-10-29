<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LeaderboardAccess;
use App\Enums\PackageTier;
use Database\Factories\UserPackageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

#[Fillable(['tier', 'user_id', 'package_id', 'name', 'price', 'tryout_quota', 'tryouts_used', 'tryout_tokens', 'leaderboard_access', 'starts_at', 'expires_at', 'revoked_at', 'granted_by', 'revoked_by', 'note'])]
class UserPackage extends Model
{
    /** @use HasFactory<UserPackageFactory> */
    use HasFactory;

    protected $attributes = ['tryout_tokens' => 0];

    protected function casts(): array
    {
        return ['tier' => PackageTier::class, 'price' => 'integer', 'tryout_quota' => 'integer', 'tryouts_used' => 'integer', 'tryout_tokens' => 'integer',
            'leaderboard_access' => LeaderboardAccess::class,
            'starts_at' => 'datetime', 'expires_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class)->withTrashed();
    }

    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class);
    }

    public function exams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class);
    }

    /** @param Builder<UserPackage> $query */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('revoked_at')->where('starts_at', '<=', now())
            ->where(fn (Builder $expiry) => $expiry->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null && $this->starts_at->lessThanOrEqualTo(now())
            && ($this->expires_at === null || $this->expires_at->isAfter(now()));
    }

    public function remainingTryouts(): ?int
    {
        return $this->tryout_quota === null ? null : max(0, $this->tryout_quota - $this->tryouts_used);
    }

    public function hasTryoutQuota(): bool
    {
        return $this->tryout_quota === null || $this->tryouts_used < $this->tryout_quota;
    }

    public function redeemTryoutToken(User $user, Exam $exam): void
    {
        abort_unless($this->user_id === $user->id && ! $user->isAdmin(), 404);

        DB::transaction(function () use ($user, $exam): void {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $entitlement = self::query()->whereKey($this->id)->whereBelongsTo($user)->lockForUpdate()->firstOrFail();
            abort_unless($entitlement->isActive(), 403, 'Paket sudah tidak aktif.');
            $exam = Exam::query()->availableForTokens()->whereKey($exam->id)->sharedLock()->firstOrFail();
            if ($entitlement->exams()->whereKey($exam->id)->exists()) {
                return;
            }
            if ($entitlement->tryout_tokens < 1 || ! $entitlement->hasTryoutQuota()) {
                throw ValidationException::withMessages(['exam' => 'Token atau kuota percobaan paket sudah habis.']);
            }
            if ($user->userPackages()->active()
                ->whereHas('exams', fn (Builder $query) => $query->whereKey($exam->id))
                ->where(fn (Builder $quota) => $quota->whereNull('tryout_quota')->orWhereColumn('tryouts_used', '<', 'tryout_quota'))
                ->exists()) {
                throw ValidationException::withMessages(['exam' => 'Tryout ini masih memiliki kuota percobaan. Gunakan percobaan yang tersedia terlebih dahulu.']);
            }
            if (ExamAttempt::query()->whereBelongsTo($user)->where('kind', 'tryout')->where('bank_slug', $exam->slug)
                ->where('status', ExamAttempt::IN_PROGRESS)
                ->where(fn (Builder $running) => $running->whereNull('expires_at')->orWhere('expires_at', '>', now()))->exists()) {
                throw ValidationException::withMessages(['exam' => 'Selesaikan sesi tryout yang masih berjalan sebelum menggunakan token lagi.']);
            }
            $exam->load(['topic' => fn (Builder|Relation $query): Builder|Relation => $query->withLearningProgress($user)]);
            if (! $exam->topicIsReadyFor($user)) {
                throw ValidationException::withMessages(['exam' => 'Selesaikan semua materi pada topik ini sebelum membuka tryout.']);
            }
            $entitlement->exams()->attach($exam);
            $entitlement->decrement('tryout_tokens');
        });
    }

    public function statusLabel(): string
    {
        if ($this->revoked_at !== null) {
            return 'Dicabut';
        }
        if ($this->starts_at->isAfter(now())) {
            return 'Belum aktif';
        }

        return $this->isActive() ? 'Aktif' : 'Kedaluwarsa';
    }

    public static function grant(User $user, Package $package, User $admin, ?string $note = null): self
    {
        abort_unless($admin->isAdmin(), 403);

        return DB::transaction(function () use ($user, $package, $admin, $note): self {
            $user = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $package = Package::query()->whereKey($package->id)->lockForUpdate()->firstOrFail();
            if (! $user->isActive() || $user->isAdmin() || ! $package->is_active) {
                throw ValidationException::withMessages(['email' => 'Paket aktif hanya dapat diberikan kepada peserta aktif.']);
            }
            if ($user->userPackages()->active()->whereBelongsTo($package)->exists()) {
                throw ValidationException::withMessages(['email' => 'Peserta sudah memiliki paket ini yang masih aktif.']);
            }
            if ($user->transactions()->whereBelongsTo($package)->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages(['email' => 'Peserta memiliki pembayaran paket yang masih pending. Selesaikan transaksi terlebih dahulu.']);
            }
            $materialIds = $package->materials()->pluck('materials.id')->all();
            $examIds = $package->exams()->pluck('exams.id')->all();
            if (! $package->hasAvailableContent()) {
                throw ValidationException::withMessages(['email' => 'Konten paket tidak lagi tersedia. Periksa materi dan sesi paket sebelum aktivasi.']);
            }
            $startsAt = now()->startOfSecond();
            $entitlement = self::create([
                'user_id' => $user->id, 'package_id' => $package->id, 'name' => $package->name, 'price' => $package->price,
                'leaderboard_access' => $package->leaderboard_access, 'tier' => $package->tier,
                'tryout_quota' => $package->tryout_quota, 'tryouts_used' => 0, 'starts_at' => $startsAt,
                'tryout_tokens' => $package->tryout_tokens,
                'expires_at' => $package->duration_days === null ? null : $startsAt->copy()->addDays($package->duration_days),
                'granted_by' => $admin->id, 'note' => $note,
            ]);
            $entitlement->materials()->sync($materialIds);
            $entitlement->exams()->sync($examIds);

            return $entitlement;
        });
    }

    public function revoke(User $admin): void
    {
        abort_unless($admin->isAdmin(), 403);
        DB::transaction(function () use ($admin): void {
            User::withTrashed()->whereKey($this->user_id)->lockForUpdate()->firstOrFail();
            self::query()->whereKey($this->id)->whereNull('revoked_at')
                ->update(['revoked_at' => now(), 'revoked_by' => $admin->id]);
        });
    }
}
