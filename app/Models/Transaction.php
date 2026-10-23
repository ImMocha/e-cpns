<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TransactionStatus;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['order_id', 'user_id', 'package_id', 'user_package_id', 'voucher_id', 'voucher_code', 'discount_amount', 'provider', 'is_production', 'amount', 'package_snapshot', 'status', 'provider_reference', 'provider_status', 'payment_type', 'payment_url', 'expires_at', 'paid_at', 'checked_at', 'raw_payload'])]
#[Hidden(['raw_payload', 'payment_url'])]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => TransactionStatus::class, 'amount' => 'integer', 'discount_amount' => 'integer', 'is_production' => 'boolean',
            'package_snapshot' => 'array', 'raw_payload' => 'array',
            'expires_at' => 'datetime', 'paid_at' => 'datetime', 'checked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    #[Scope]
    protected function reservingVoucher(Builder $query): Builder
    {
        return $query->whereNotIn('status', [TransactionStatus::Failed, TransactionStatus::Expired]);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class)->withTrashed();
    }

    public function userPackage(): BelongsTo
    {
        return $this->belongsTo(UserPackage::class);
    }

    public function formattedAmount(): string
    {
        return 'Rp'.number_format($this->amount, 0, ',', '.');
    }
}
