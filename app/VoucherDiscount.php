<?php

declare(strict_types=1);

namespace App;

use App\Models\Package;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VoucherDiscount
{
    /** @return array{voucher: ?Voucher, discount: int, amount: int} */
    public function quote(Package $package, ?string $code, bool $lock = false): array
    {
        $code = Str::upper(trim($code ?? ''));
        if ($code === '') {
            return ['voucher' => null, 'discount' => 0, 'amount' => $package->price];
        }

        $voucher = Voucher::query()->when($lock, fn (Builder $query) => $query->lockForUpdate())->where('code', $code)->first();
        $message = match (true) {
            $voucher === null => 'Kode voucher tidak ditemukan.',
            ! $voucher->is_active => 'Voucher sedang nonaktif.',
            $voucher->starts_on !== null && $voucher->starts_on->gt(today()) => 'Voucher belum mulai berlaku.',
            $voucher->ends_on !== null && $voucher->ends_on->lt(today()) => 'Voucher sudah kedaluwarsa.',
            $voucher->package_id !== null && $voucher->package_id !== $package->id => 'Voucher tidak berlaku untuk paket ini.',
            $package->price === 0 => 'Voucher tidak dapat digunakan pada paket gratis.',
            $package->price < $voucher->minimum_amount => 'Harga paket belum memenuhi minimum transaksi voucher.',
            $voucher->usage_limit !== null && $voucher->transactions()->reservingVoucher()->count() >= $voucher->usage_limit => 'Kuota voucher sudah habis.',
            default => null,
        };
        if ($message !== null) {
            throw ValidationException::withMessages(['voucher_code' => $message]);
        }

        $discount = min($package->price, $voucher->type === 'percentage'
            ? intdiv($package->price * $voucher->value, 100) : $voucher->value);

        return ['voucher' => $voucher, 'discount' => $discount, 'amount' => $package->price - $discount];
    }
}
