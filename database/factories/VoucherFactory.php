<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Voucher> */
class VoucherFactory extends Factory
{
    public function definition(): array
    {
        return ['code' => strtoupper(fake()->unique()->bothify('VOUCHER########')),
            'type' => 'fixed', 'value' => 20000, 'minimum_amount' => 0, 'is_active' => true];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function percentage(int $value = 10): static
    {
        return $this->state(fn (): array => ['type' => 'percentage', 'value' => $value]);
    }
}
