<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\VoucherFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'type', 'value', 'starts_on', 'ends_on', 'minimum_amount', 'usage_limit', 'package_id', 'is_active'])]
class Voucher extends Model
{
    /** @use HasFactory<VoucherFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['value' => 'integer', 'minimum_amount' => 'integer', 'usage_limit' => 'integer',
            'package_id' => 'integer', 'is_active' => 'boolean', 'starts_on' => 'date', 'ends_on' => 'date'];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class)->withTrashed();
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    #[Scope]
    protected function current(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereNull('starts_on')->orWhere('starts_on', '<=', today()->toDateString()))
            ->where(fn (Builder $query) => $query->whereNull('ends_on')->orWhere('ends_on', '>=', today()->toDateString()));
    }

    public function statusLabel(): string
    {
        return match (true) {
            ! $this->is_active => 'Nonaktif',
            $this->ends_on !== null && $this->ends_on->lt(today()) => 'Kedaluwarsa',
            $this->starts_on !== null && $this->starts_on->gt(today()) => 'Terjadwal',
            $this->usage_limit !== null && $this->reserved_count >= $this->usage_limit => 'Kuota habis',
            default => 'Aktif',
        };
    }

    public function formattedValue(): string
    {
        return $this->type === 'percentage' ? $this->value.'%' : 'Rp'.number_format($this->value, 0, ',', '.');
    }
}
