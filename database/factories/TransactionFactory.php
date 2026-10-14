<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TransactionStatus;
use App\Models\Package;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Transaction> */
class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => 'GANESHA-NUSA-'.Str::uuid(), 'user_id' => User::factory(), 'package_id' => Package::factory(),
            'provider' => 'midtrans', 'is_production' => false, 'amount' => 150000,
            'package_snapshot' => ['name' => 'Paket Persiapan', 'duration_days' => 30, 'tryout_quota' => 2, 'materials' => [], 'exams' => []],
            'status' => TransactionStatus::Pending, 'expires_at' => now()->addDay(),
        ];
    }
}
