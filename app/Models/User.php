<?php

namespace App\Models;

use App\Enums\LeaderboardAccess;
use App\Enums\Province;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'email',
    'password',
    'email_verified_at',
    'role',
    'status',
    'phone',
    'address',
    'province',
    'institution',
    'bio',
    'notification_preferences',
    'avatar_path',
    'last_login_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    public const DEFAULT_NOTIFICATION_PREFERENCES = [
        'new_tryout' => true,
        'updated_materials' => true,
        'payment_updates' => true,
        'package_recommendations' => false,
        'platform_announcements' => false,
    ];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    public function examAttempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function materialProgress(): HasMany
    {
        return $this->hasMany(MaterialProgress::class);
    }

    public function userPackages(): HasMany
    {
        return $this->hasMany(UserPackage::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'notification_preferences' => 'array',
            'role' => UserRole::class,
            'province' => Province::class,
            'status' => UserStatus::class,
        ];
    }

    // ---- Authorization helpers ------------------------------------------------

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function wantsEmailNotification(string $preference): bool
    {
        return ! $this->trashed()
            && $this->isActive()
            && $this->hasVerifiedEmail()
            && (bool) ($this->notification_preferences[$preference] ?? self::DEFAULT_NOTIFICATION_PREFERENCES[$preference] ?? false);
    }

    public function canViewExplanations(): bool
    {
        return $this->isAdmin() || $this->userPackages()->active()->where('price', '>', 0)->exists();
    }

    public function leaderboardAccess(): LeaderboardAccess
    {
        if ($this->isAdmin()) {
            return LeaderboardAccess::National;
        }

        $access = $this->userPackages()->active()->pluck('leaderboard_access');

        if ($access->contains(LeaderboardAccess::National)) {
            return LeaderboardAccess::National;
        }

        return $access->contains(LeaderboardAccess::Local) ? LeaderboardAccess::Local : LeaderboardAccess::None;
    }

    // ---- Scopes ---------------------------------------------------------------

    /** @param  Builder<User>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', UserStatus::Active->value);
    }

    /** @param  Builder<User>  $query */
    public function scopeRole(Builder $query, UserRole|string $role): void
    {
        $query->where('role', $role instanceof UserRole ? $role->value : $role);
    }

    /**
     * Pencarian untuk layar pengelolaan user.
     *
     * Catatan: nilai filter diambil lewat `use`, bukan dari argumen callback
     * `when()`. `when($condition, $callback)` mengirim *nilai kondisi* ke
     * callback, bukan nilai yang ingin difilter.
     *
     * @param  array{q?: string, role?: string|null, status?: string|null}  $filters
     * @return Builder<User>
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        $search = trim((string) ($filters['q'] ?? ''));

        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('institution', 'like', "%{$search}%");
            });
        }

        if (in_array($filters['role'] ?? null, UserRole::options(), true)) {
            $query->where('role', $filters['role']);
        }

        if (in_array($filters['status'] ?? null, UserStatus::options(), true)) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }

    // ---- Presentation helpers --------------------------------------------------

    /** Inisial untuk avatar, maksimal dua huruf. */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');
    }

    /** Warna avatar deterministik agar user yang sama selalu dapat warna sama. */
    public function avatarTone(): string
    {
        $tones = ['pink', 'mint', 'amber', 'violet', 'coral'];

        return $tones[crc32($this->email) % count($tones)];
    }

    public function hasAvatar(): bool
    {
        return filled($this->avatar_path);
    }
}
