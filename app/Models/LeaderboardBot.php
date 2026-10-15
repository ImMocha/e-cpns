<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Province;
use Database\Factories\LeaderboardBotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['source_key', 'name', 'province', 'score', 'is_visible'])]
class LeaderboardBot extends Model
{
    /** @use HasFactory<LeaderboardBotFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['province' => Province::class, 'score' => 'integer', 'is_visible' => 'boolean'];
    }
}
