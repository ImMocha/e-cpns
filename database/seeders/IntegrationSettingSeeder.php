<?php

namespace Database\Seeders;

use App\Models\IntegrationSetting;
use Illuminate\Database\Seeder;

class IntegrationSettingSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['midtrans', 'mail'] as $key) {
            IntegrationSetting::query()->firstOrCreate(['key' => $key], ['version' => 0]);
        }
    }
}
