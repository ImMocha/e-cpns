<?php

namespace Database\Factories;

use App\Enums\ColorTone;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Section>
 */
class SectionFactory extends Factory
{
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(2, true));

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999999),
            'code' => Str::upper(substr(preg_replace('/[^A-Za-z]/', '', $name), 0, 3)).'-'.fake()->unique()->numberBetween(1, 9999),
            'description' => fake()->optional()->sentence(10),
            'position' => fake()->numberBetween(1, 20),
            'is_active' => true,
            'icon' => fake()->randomElement(Section::ICONS),
            'tone' => fake()->randomElement(ColorTone::options()),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }

    public function plain(): static
    {
        return $this->state(fn (array $attributes) => ['icon' => null, 'tone' => null]);
    }
}
