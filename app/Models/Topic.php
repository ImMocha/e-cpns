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

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('section_id')->orderBy('position')->orderBy('id');
    }
}
