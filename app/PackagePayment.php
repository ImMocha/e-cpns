<?php

declare(strict_types=1);

namespace App;

use App\Enums\TransactionStatus;
use App\Models\Exam;
use App\Models\IntegrationSetting;
use App\Models\Material;
use App\Models\Package;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PackagePayment
{
    public function __construct(private PaymentGatewayContract $gateway, private IntegrationSettings $settings, private VoucherDiscount $discounts) {}

    public function checkout(User $user, Package $package, ?string $voucherCode = null): Transaction
    {
        return DB::transaction(function () use ($user, $package, $voucherCode): Transaction {
            IntegrationSetting::query()->lockForUpdate()->findOrFail('midtrans');
            $user = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $package = Package::query()->whereKey($package->id)->lockForUpdate()->firstOrFail();
            if (! $user->isActive() || $user->isAdmin() || ! $package->is_active) {
                throw ValidationException::withMessages(['package_id' => 'Paket ini tidak dapat dibeli.']);
            }
            $pending = Transaction::query()->whereBelongsTo($user)->whereBelongsTo($package)->where('status', TransactionStatus::Pending)->first();
            if ($pending !== null) {
                if (filled($voucherCode) && Str::upper(trim($voucherCode)) !== $pending->voucher_code) {
                    throw ValidationException::withMessages(['voucher_code' => 'Paket ini memiliki pesanan menunggu pembayaran. Selesaikan pesanan tersebut sebelum mengganti voucher.']);
                }

                return $pending;
            }
            if (! $package->hasAvailableContent()) {
                throw ValidationException::withMessages(['package_id' => 'Konten paket sedang tidak tersedia. Pilih paket lain atau hubungi admin.']);
            }
            $materials = $package->materials()->get();
            $exams = $package->exams()->get();
            $quote = $this->discounts->quote($package, $voucherCode, lock: true);
            if ($quote['amount'] > 0 && ! $this->gateway->configured()) {
                throw ValidationException::withMessages(['package_id' => 'Pembayaran belum tersedia. Silakan hubungi admin.']);
            }
            $paymentSettings = $this->settings->midtrans();
            $transaction = Transaction::create([
                'order_id' => 'GANESHA-NUSA-'.Str::uuid(), 'user_id' => $user->id, 'package_id' => $package->id,
                'provider' => $quote['amount'] === 0 ? 'free' : 'midtrans',
                'voucher_id' => $quote['voucher']?->id, 'voucher_code' => $quote['voucher']?->code, 'discount_amount' => $quote['discount'],
                'is_production' => $paymentSettings['is_production'], 'amount' => $quote['amount'],
                'midtrans_server_key' => $quote['amount'] === 0 ? null : $paymentSettings[$paymentSettings['is_production'] ? 'production_server_key' : 'sandbox_server_key'],
                'package_snapshot' => [
                    'leaderboard_access' => $package->leaderboard_access->value, 'tier' => $package->tier?->value,
                    'name' => $package->name, 'duration_days' => $package->duration_days, 'tryout_quota' => $package->tryout_quota,
                    'tryout_tokens' => $package->tryout_tokens,
                    'materials' => $materials->map(fn (Material $material): array => ['id' => $material->id, 'title' => $material->title])->all(),
                    'exams' => $exams->map(fn (Exam $exam): array => ['id' => $exam->id, 'title' => $exam->title])->all(),
                ],
                'status' => TransactionStatus::Pending, 'expires_at' => now()->addDay(),
            ]);
            if ($transaction->amount === 0) {
                $this->activate($transaction);
                $transaction->update(['status' => TransactionStatus::Paid, 'paid_at' => now()]);
            }

            return $transaction;
        });
    }

    public function prepare(Transaction $transaction): Transaction
    {
        return DB::transaction(function () use ($transaction): Transaction {
            User::withTrashed()->whereKey($transaction->user_id)->lockForUpdate()->firstOrFail();
            $transaction = Transaction::query()->whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            if ($transaction->status !== TransactionStatus::Pending) {
                return $transaction;
            }
            if ($transaction->expires_at->isPast()) {
                throw new RuntimeException('Batas pembayaran sudah lewat. Periksa status transaksi sebelum membuat pesanan baru.');
            }
            if ($transaction->payment_url !== null) {
                return $transaction;
            }
            $transaction->update(['payment_url' => $this->gateway->create($transaction)]);

            return $transaction;
        });
    }

    public function refresh(Transaction $transaction): Transaction
    {
        if ($transaction->provider !== 'midtrans') {
            return $transaction;
        }

        return DB::transaction(function () use ($transaction): Transaction {
            User::withTrashed()->whereKey($transaction->user_id)->lockForUpdate()->firstOrFail();
            $transaction = Transaction::query()->whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            $payload = $this->gateway->status($transaction);
            if ($payload === null) {
                $transaction->update([
                    'checked_at' => now(),
                    'status' => $transaction->status === TransactionStatus::Pending && $transaction->expires_at->isPast()
                        ? TransactionStatus::Expired : $transaction->status,
                ]);

                return $transaction;
            }
            $amount = $payload['gross_amount'] ?? null;
            if (($payload['order_id'] ?? null) !== $transaction->order_id
                || ! is_string($amount) || ! preg_match('/^([0-9]+)(?:\\.00)?$/D', $amount, $matches)
                || (int) $matches[1] !== $transaction->amount
                || (isset($payload['currency']) && $payload['currency'] !== 'IDR')
                || ! is_string($payload['transaction_id'] ?? null)
                || ! is_string($payload['transaction_status'] ?? null)
                || ($transaction->user_package_id !== null && $transaction->provider_reference !== null
                    && $transaction->provider_reference !== $payload['transaction_id'])) {
                throw new RuntimeException('Identitas atau jumlah pembayaran Midtrans tidak cocok.');
            }
            $providerStatus = strtolower($payload['transaction_status']);
            $fraud = $payload['fraud_status'] ?? null;
            $status = match ($providerStatus) {
                'settlement' => $fraud === null || $fraud === 'accept' ? TransactionStatus::Paid : TransactionStatus::Pending,
                'capture' => $fraud === 'accept' ? TransactionStatus::Paid : ($fraud === 'deny' ? TransactionStatus::Failed : TransactionStatus::Pending),
                'deny', 'cancel', 'failure' => TransactionStatus::Failed,
                'expire' => TransactionStatus::Expired,
                'refund', 'chargeback' => TransactionStatus::Refunded,
                'partial_refund', 'partial_chargeback' => TransactionStatus::PartiallyRefunded,
                default => TransactionStatus::Pending,
            };
            if ($transaction->status === TransactionStatus::Refunded
                || ($transaction->user_package_id !== null && in_array($status, [TransactionStatus::Pending, TransactionStatus::Failed, TransactionStatus::Expired], true))
                || ($transaction->status === TransactionStatus::PartiallyRefunded && $status === TransactionStatus::Paid)) {
                $status = $transaction->status;
            }
            if (in_array($status, [TransactionStatus::Paid, TransactionStatus::PartiallyRefunded], true) && $transaction->user_package_id === null) {
                $this->activate($transaction);
            }
            if ($status === TransactionStatus::Refunded && $transaction->user_package_id !== null) {
                UserPackage::query()->whereKey($transaction->user_package_id)->whereNull('revoked_at')->update(['revoked_at' => now()]);
            }
            $transaction->update([
                'status' => $status, 'provider_reference' => $payload['transaction_id'], 'provider_status' => $providerStatus,
                'payment_type' => $payload['payment_type'] ?? null, 'raw_payload' => $payload,
                'paid_at' => in_array($status, [TransactionStatus::Paid, TransactionStatus::PartiallyRefunded], true) ? ($transaction->paid_at ?? now()) : $transaction->paid_at,
                'checked_at' => now(),
            ]);

            return $transaction;
        });
    }

    private function activate(Transaction $transaction): void
    {
        $snapshot = $transaction->package_snapshot;
        $startsAt = now()->startOfSecond();
        $entitlement = UserPackage::create([
            'leaderboard_access' => $snapshot['leaderboard_access'] ?? 'none', 'tier' => $snapshot['tier'] ?? null,
            'user_id' => $transaction->user_id, 'package_id' => $transaction->package_id, 'name' => $snapshot['name'],
            'price' => $transaction->amount, 'tryout_quota' => $snapshot['tryout_quota'], 'tryouts_used' => 0,
            'tryout_tokens' => $snapshot['tryout_tokens'] ?? 0,
            'starts_at' => $startsAt,
            'expires_at' => $snapshot['duration_days'] === null ? null : $startsAt->copy()->addDays($snapshot['duration_days']),
            'note' => 'Pembelian '.$transaction->order_id,
        ]);
        $entitlement->materials()->sync(Material::withTrashed()->whereKey(array_column($snapshot['materials'], 'id'))->pluck('id')->all());
        $entitlement->exams()->sync(Exam::withTrashed()->whereKey(array_column($snapshot['exams'], 'id'))->pluck('id')->all());
        $transaction->update(['user_package_id' => $entitlement->id]);
    }
}
