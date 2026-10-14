<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Expired = 'expired';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu pembayaran',
            self::Paid => 'Lunas',
            self::Failed => 'Gagal / dibatalkan',
            self::Expired => 'Kedaluwarsa',
            self::Refunded => 'Dikembalikan',
            self::PartiallyRefunded => 'Dikembalikan sebagian',
        };
    }
}
