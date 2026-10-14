<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LeaderboardAccess;
use Database\Factories\PackageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'description', 'price', 'duration_days', 'tryout_quota', 'leaderboard_access', 'is_active', 'position', 'created_by', 'updated_by'])]
class Package extends Model
{
    /** @use HasFactory<PackageFactory> */
    use HasFactory, SoftDeletes;

    protected $attributes = ['leaderboard_access' => 'none'];

    protected function casts(): array
    {
        return ['price' => 'integer', 'duration_days' => 'integer', 'tryout_quota' => 'integer', 'leaderboard_access' => LeaderboardAccess::class, 'is_active' => 'boolean', 'position' => 'integer'];
    }

    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class);
    }

    public function exams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class);
    }

    public function userPackages(): HasMany
    {
        return $this->hasMany(UserPackage::class);
    }

    /** @param Builder<Package> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function formattedPrice(): string
    {
        return 'Rp'.number_format($this->price, 0, ',', '.');
    }

    public function durationLabel(): string
    {
        if ($this->duration_days === null) {
            return 'Tanpa batas waktu';
        }

        return $this->duration_days === 365 ? '1 tahun' : $this->duration_days.' hari';
    }
}
