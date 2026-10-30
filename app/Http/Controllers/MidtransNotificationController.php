<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\MidtransGateway;
use App\Models\Transaction;
use App\PackagePayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MidtransNotificationController extends Controller
{
    public function __invoke(Request $request, MidtransGateway $gateway, PackagePayment $payments): JsonResponse
    {
        $orderId = $request->input('order_id');
        abort_unless(is_string($orderId) && strlen($orderId) <= 50, 403, 'Signature tidak valid.');
        $transaction = Transaction::query()->where('provider', 'midtrans')->where('order_id', $orderId)->first();
        $paymentConfigured = $transaction === null
            ? $gateway->configuredFor(false) || $gateway->configuredFor(true)
            : $gateway->configuredForTransaction($transaction);
        abort_unless($paymentConfigured, 503, 'Pembayaran belum dikonfigurasi.');
        abort_unless($gateway->validSignature($request->all(), $transaction), 403, 'Signature tidak valid.');
        abort_if($transaction === null, 404);
        try {
            $transaction = $payments->refresh($transaction);
            if ($transaction->provider_status === null) {
                return response()->json(['message' => 'Status belum tersedia. Kirim ulang notifikasi.'], 503);
            }
        } catch (RuntimeException $exception) {
            Log::warning('Verifikasi pembayaran Midtrans tertunda.', ['order_id' => $transaction->order_id, 'reason' => $exception->getMessage()]);

            return response()->json(['message' => 'Verifikasi belum berhasil. Kirim ulang notifikasi.'], 503);
        }

        return response()->json(['message' => 'OK']);
    }
}
