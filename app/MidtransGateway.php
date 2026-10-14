<?php

declare(strict_types=1);

namespace App;

use App\Models\Transaction;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class MidtransGateway implements PaymentGatewayContract
{
    public function configured(): bool
    {
        return is_string(config('services.midtrans.server_key')) && config('services.midtrans.server_key') !== '';
    }

    private function client(Transaction $transaction): PendingRequest
    {
        if (! $this->configured() || $transaction->is_production !== (bool) config('services.midtrans.is_production')) {
            throw new RuntimeException('Konfigurasi Midtrans tidak tersedia untuk transaksi ini.');
        }

        return Http::acceptJson()->asJson()->withBasicAuth(config('services.midtrans.server_key'), '')
            ->connectTimeout(5)->timeout(15);
    }

    public function create(Transaction $transaction): string
    {
        $host = $transaction->is_production ? 'https://app.midtrans.com' : 'https://app.sandbox.midtrans.com';
        try {
            $payload = $this->client($transaction)->post($host.'/snap/v1/transactions', [
                'transaction_details' => ['order_id' => $transaction->order_id, 'gross_amount' => $transaction->amount],
                'item_details' => [['id' => (string) $transaction->package_id, 'price' => $transaction->amount, 'quantity' => 1,
                    'name' => Str::limit($transaction->package_snapshot['name'], 50, '')]],
                'customer_details' => ['first_name' => Str::limit($transaction->user->name, 100, ''), 'email' => $transaction->user->email],
                'callbacks' => ['finish' => route('transactions.show', $transaction)],
                'expiry' => ['start_time' => $transaction->created_at->copy()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s O'), 'unit' => 'hour', 'duration' => 24],
                'page_expiry' => ['unit' => 'hour', 'duration' => 24],
            ])->throw()->json();
        } catch (ConnectionException|RequestException) {
            throw new RuntimeException('Midtrans belum dapat menyiapkan pembayaran. Coba lagi dari detail transaksi.');
        }

        $url = $payload['redirect_url'] ?? null;
        if (! is_string($url) || ! str_starts_with($url, $host.'/snap/')) {
            throw new RuntimeException('Respons pembayaran Midtrans tidak valid.');
        }

        return $url;
    }

    /** @return array<string, mixed>|null */
    public function status(Transaction $transaction): ?array
    {
        $host = $transaction->is_production ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
        try {
            $response = $this->client($transaction)->get($host.'/v2/'.$transaction->order_id.'/status');
            if ($response->status() === 404 || ($response->successful() && (string) $response->json('status_code') === '404')) {
                return null;
            }
            $payload = $response->throw()->json();
        } catch (ConnectionException|RequestException) {
            throw new RuntimeException('Status Midtrans belum dapat diperiksa. Coba lagi sebentar.');
        }
        if (! is_array($payload) || ! isset($payload['transaction_status'])) {
            throw new RuntimeException('Respons status Midtrans tidak valid.');
        }

        return $payload;
    }

    /** @param array<string, mixed> $payload */
    public function validSignature(array $payload): bool
    {
        if (! $this->configured()) {
            return false;
        }
        foreach (['order_id', 'status_code', 'gross_amount', 'signature_key'] as $key) {
            if (! isset($payload[$key]) || ! is_string($payload[$key])) {
                return false;
            }
        }
        $expected = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].config('services.midtrans.server_key'));

        return hash_equals($expected, $payload['signature_key']);
    }
}
