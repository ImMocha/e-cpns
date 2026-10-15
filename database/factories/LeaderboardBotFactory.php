<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Province;
use App\Models\LeaderboardBot;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LeaderboardBot> */
class LeaderboardBotFactory extends Factory
{
    public function definition(): array
    {
        return [
            'source_key' => fake()->unique()->bothify('test-########'),
            'name' => fake()->name(),
            'province' => fake()->randomElement(Province::cases()),
            'score' => fake()->numberBetween(55, 93),
        ];
    }
}
