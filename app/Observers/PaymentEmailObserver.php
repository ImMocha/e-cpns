<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Notifications\AccountEmail;

class PaymentEmailObserver
{
    public function updated(Transaction $transaction): void
    {
        $statusChanged = $transaction->wasChanged('status') && $transaction->status !== TransactionStatus::Pending;
        $paymentReady = $transaction->status === TransactionStatus::Pending
            && $transaction->wasChanged('payment_url') && filled($transaction->payment_url);

        if (! $statusChanged && ! $paymentReady) {
            return;
        }

        $user = $transaction->user;
        if ($user === null || ! $user->wantsEmailNotification('payment_updates')) {
            return;
        }

        $user->notify(new AccountEmail(
            'Status pembayaran: '.$transaction->status->label(),
            'Berikut informasi terbaru transaksi paket belajarmu. Buka detail transaksi untuk melihat pembayaran dan akses paket.',
            'Lihat transaksi',
            route('transactions.show', $transaction),
            'payment_updates',
            [
                'Nomor transaksi' => $transaction->order_id,
                'Paket' => $transaction->package_snapshot['name'],
                'Total' => $transaction->formattedAmount(),
                'Status' => $transaction->status->label(),
            ],
        ));
    }
}
