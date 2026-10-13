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
        return ['kind' => 'quiz', 'slug' => fake()->unique()->slug(3), 'title' => fake()->sentence(3),
            'status' => 'published', 'question_count' => 1, 'duration_minutes' => 0, 'points_per_correct' => 5,
            'penalty_wrong' => 0, 'random_questions' => true, 'random_options' => true];
    }

    public function tryout(): static
    {
        return $this->state(fn (): array => ['kind' => 'tryout', 'duration_minutes' => 30]);
    }
}
