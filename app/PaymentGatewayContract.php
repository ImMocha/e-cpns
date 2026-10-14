<?php

declare(strict_types=1);

namespace App;

use App\Models\Transaction;

interface PaymentGatewayContract
{
    public function configured(): bool;

    public function create(Transaction $transaction): string;

    /** @return array<string, mixed>|null */
    public function status(Transaction $transaction): ?array;

    /** @param array<string, mixed> $payload */
    public function validSignature(array $payload): bool;
}
