<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionStatus;
use App\Models\Question;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Question> */
class QuestionFactory extends Factory
{
    public function definition(): array
    {
        return ['section_id' => Section::factory(), 'title' => fake()->sentence(4),
            'body' => '<p>Berapakah hasil dari 12 + 8?</p>', 'options' => ['18', '20', '22', '24', '26'],
            'correct_option' => 1, 'explanation' => '<p>12 + 8 = 20.</p>',
            'difficulty' => QuestionDifficulty::Easy, 'status' => QuestionStatus::Draft, 'published_at' => null];
    }

    public function published(): static
    {
        return $this->state(fn (): array => ['status' => QuestionStatus::Published, 'published_at' => now()]);
    }

    public function weighted(): static
    {
        return $this->state(fn (): array => ['scoring_type' => 'weighted', 'option_scores' => [1, 2, 3, 4, 5], 'correct_option' => null]);
    }
}
