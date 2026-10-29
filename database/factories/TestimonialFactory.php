<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Testimonial> */
class TestimonialFactory extends Factory
{
    /** @return array{name: string, institution: string, content: string, is_visible: bool} */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'institution' => fake()->company(),
            'content' => fake()->paragraph(),
            'is_visible' => false,
        ];
    }

    public function visible(): static
    {
        return $this->state(fn (): array => ['is_visible' => true]);
    }
}
