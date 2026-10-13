<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Article> */
class ArticleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'section_id' => Section::factory(),
            'title' => fake()->sentence(5),
            'slug' => fake()->unique()->slug(),
            'excerpt' => fake()->sentence(),
            'body' => '<p>'.e(fake()->paragraph()).'</p>',
            'status' => ArticleStatus::Draft,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => ArticleStatus::Published, 'published_at' => now()]);
    }
}
