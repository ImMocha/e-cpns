<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TopicFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['section_id', 'name', 'description', 'position', 'is_active'])]
class Topic extends Model
{
    /** @use HasFactory<TopicFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['position' => 'integer', 'is_active' => 'boolean'];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Exam::class)->where('kind', 'quiz');
    }

    /** @param Builder<Topic> $query */
    public function scopeWithLearningProgress(Builder $query, User $user): void
    {
        $query->withCount([
            'materials as learning_materials_count' => fn (Builder $materials): Builder => $materials->visibleToMembers(),
            'materials as completed_learning_materials_count' => fn (Builder $materials): Builder => $materials->visibleToMembers()
                ->whereHas('progressRecords', fn (Builder $progress): Builder => $progress->whereBelongsTo($user)->whereNotNull('completed_at')),
        ]);
    }

    public function hasCompletedMaterials(): bool
    {
        return $this->learning_materials_count > 0
            && (int) $this->completed_learning_materials_count === (int) $this->learning_materials_count;
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('section_id')->orderBy('position')->orderBy('id');
    }
}
