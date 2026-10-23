<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Voucher;
use Illuminate\Database\Seeder;

class VoucherSeeder extends Seeder
{
    public function run(): void
    {
        Voucher::firstOrCreate(['code' => 'HEMAT20K'], [
            'type' => 'fixed', 'value' => 20000, 'minimum_amount' => 100000, 'usage_limit' => 100, 'is_active' => false,
        ]);
    }
}
