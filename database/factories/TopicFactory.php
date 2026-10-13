<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Section;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Topic>
 */
class TopicFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'section_id' => Section::factory(),
            'name' => fake()->unique()->words(3, true),
            'description' => null,
            'position' => 0,
            'is_active' => true,
        ];
    }
}
