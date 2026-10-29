<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Exam;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Exam> */
class ExamFactory extends Factory
{
    public function definition(): array
    {
        return ['kind' => 'quiz', 'access_level' => 'free', 'slug' => fake()->unique()->slug(3), 'title' => fake()->sentence(3),
            'status' => 'published', 'question_count' => 1, 'duration_minutes' => 0, 'points_per_correct' => 5,
            'penalty_wrong' => 0, 'random_questions' => true, 'random_options' => true];
    }

    public function tryout(): static
    {
        return $this->state(fn (): array => ['kind' => 'tryout', 'duration_minutes' => 30]);
    }

    public function practice(): static
    {
        return $this->state(fn (): array => ['kind' => 'practice', 'duration_minutes' => 30]);
    }

    public function claimable(): static
    {
        return $this->state(fn (): array => [
            'access_level' => 'free', 'free_claim_enabled' => true,
            'free_claim_terms' => 'Follow Instagram, upload poster ke Story, lalu kirim screenshot follow dan Story.',
            'free_claim_instagram_url' => 'https://www.instagram.com/ganeshanusa/',
        ]);
    }
}
