<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\TransactionStatus;
use App\MidtransGateway;
use App\Models\Transaction;
use App\PackagePayment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SyncMidtransTransactions extends Command
{
    protected $signature = 'payments:sync-midtrans {--limit=100 : Jumlah transaksi maksimal per proses}';

    protected $description = 'Memeriksa pembayaran pending yang belum menerima notifikasi Midtrans';

    public function handle(PackagePayment $payments, MidtransGateway $gateway): int
    {
        if (! $gateway->configured()) {
            $this->warn('Midtrans belum dikonfigurasi.');

            return self::SUCCESS;
        }
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]);
        if ($limit === false) {
            $this->error('Limit harus antara 1 dan 1000.');

            return self::FAILURE;
        }
        $transactions = Transaction::query()->where('provider', 'midtrans')->where('status', TransactionStatus::Pending)
            ->where('is_production', (bool) config('services.midtrans.is_production'))
            ->where(fn ($query) => $query->whereNull('checked_at')->orWhere('checked_at', '<=', now()->subMinutes(10)))
            ->orderBy('checked_at')->orderBy('id')->limit($limit)->get();
        $failed = 0;
        foreach ($transactions as $transaction) {
            try {
                $payments->refresh($transaction);
            } catch (RuntimeException $exception) {
                $failed++;
                $transaction->update(['checked_at' => now()]);
                Log::warning('Sinkronisasi Midtrans tertunda.', ['order_id' => $transaction->order_id, 'reason' => $exception->getMessage()]);
            }
        }
        $this->info('Diperiksa: '.$transactions->count().'. Tertunda: '.$failed.'.');

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
