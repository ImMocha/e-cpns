<?php

namespace App\Models;

use Database\Factories\MaterialProgressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'material_id', 'last_viewed_at', 'completed_at'])]
class MaterialProgress extends Model
{
    /** @use HasFactory<MaterialProgressFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['last_viewed_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
