<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\PackagePayment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        return view('Dashboard.User.transactions.index', [
            'transactions' => $request->user()->transactions()->latest('id')->paginate(15),
        ]);
    }

    public function show(Request $request, Transaction $transaction, PackagePayment $payments): View
    {
        abort_unless($transaction->user_id === $request->user()->id, 404);
        $paymentError = null;
        if ($request->query('order_id') === $transaction->order_id
            && ($transaction->checked_at === null || $transaction->checked_at->lt(now()->subSeconds(30)))) {
            try {
                $transaction = $payments->refresh($transaction);
            } catch (RuntimeException $exception) {
                $paymentError = $exception->getMessage();
            }
        }

        return view('Dashboard.User.transactions.show', [
            'transaction' => $transaction->load('userPackage'), 'paymentError' => $paymentError,
        ]);
    }

    public function refresh(Request $request, Transaction $transaction, PackagePayment $payments): RedirectResponse
    {
        abort_unless($transaction->user_id === $request->user()->id, 404);
        try {
            $payments->refresh($transaction);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['payment' => $exception->getMessage()]);
        }

        return redirect()->route('transactions.show', $transaction)->with('success', 'Status pembayaran sudah diperiksa.');
    }

    public function pay(Request $request, Transaction $transaction, PackagePayment $payments): RedirectResponse
    {
        abort_unless($transaction->user_id === $request->user()->id, 404);
        try {
            if ($transaction->expires_at->isPast()) {
                $transaction = $payments->refresh($transaction);
            }
            $transaction = $payments->prepare($transaction);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['payment' => $exception->getMessage()]);
        }
        if ($transaction->status !== TransactionStatus::Pending || $transaction->payment_url === null) {
            return redirect()->route('transactions.show', $transaction);
        }

        return redirect()->away($transaction->payment_url);
    }
}
