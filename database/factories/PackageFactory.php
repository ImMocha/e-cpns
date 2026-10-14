<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Package> */
class PackageFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->sentence(3), 'description' => fake()->sentence(),
            'price' => 150000, 'duration_days' => 30, 'tryout_quota' => 2, 'is_active' => true, 'position' => 0];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function unlimited(): static
    {
        return $this->state(fn (): array => ['duration_days' => null, 'tryout_quota' => null]);
    }
}
