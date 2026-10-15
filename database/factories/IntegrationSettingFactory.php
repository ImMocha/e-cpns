<?php

namespace Database\Factories;

use App\Models\IntegrationSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<IntegrationSetting> */
class IntegrationSettingFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['key' => 'midtrans', 'settings' => null, 'version' => 0];
    }
}
