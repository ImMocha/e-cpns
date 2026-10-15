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

    /** @param Builder<Package> $query */
    public function scopeWithContentAvailability(Builder $query): void
    {
        $query->withCount(self::contentAvailabilityCounts());
    }

    public function hasAvailableContent(): bool
    {
        if (! $this->exists) {
            return false;
        }
        if (! array_key_exists('selected_materials_count', $this->getAttributes())) {
            $this->loadCount(self::contentAvailabilityCounts());
        }

        $selectedMaterials = (int) $this->selected_materials_count;
        $selectedExams = (int) $this->selected_exams_count;

        return $selectedMaterials + $selectedExams > 0
            && (int) $this->available_materials_count === $selectedMaterials
            && (int) $this->available_exams_count === $selectedExams;
    }

    /** @return array<string, \Closure> */
    private static function contentAvailabilityCounts(): array
    {
        return [
            'materials as selected_materials_count' => fn (Builder $query): Builder => $query->withTrashed(),
            'materials as available_materials_count' => fn (Builder $query): Builder => $query->visibleToMembers(),
            'exams as selected_exams_count' => fn (Builder $query): Builder => $query->withTrashed(),
            'exams as available_exams_count' => fn (Builder $query): Builder => $query->published(),
        ];
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
