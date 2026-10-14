<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Package;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<UserPackage> */
class UserPackageFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'package_id' => Package::factory(),
            'name' => 'Paket Belajar Peserta', 'price' => 150000, 'tryout_quota' => 2,
            'tryouts_used' => 0, 'starts_at' => now(), 'expires_at' => now()->addDays(30)];
    }

    public function expired(): static
    {
        return $this->state(fn (): array => ['starts_at' => now()->subDays(30), 'expires_at' => now()]);
    }

    public function revoked(): static
    {
        return $this->state(fn (): array => ['revoked_at' => now()]);
    }
}
