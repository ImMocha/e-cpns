<?php

namespace Database\Factories;

use App\Enums\ExamBank;
use App\Models\ExamAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamAttempt>
 */
class ExamAttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $definition = ExamBank::QuizSilogisme->definition();

        return [
            'user_id' => User::factory(),
            'kind' => 'quiz',
            'bank_slug' => ExamBank::QuizSilogisme->value,
            'title' => $definition['title'],
            'status' => ExamAttempt::IN_PROGRESS,
            'question_snapshot' => $definition,
            'answers' => [],
            'flags' => [],
            'current_index' => 0,
            'started_at' => now(),
        ];
    }

    public function tryout(): static
    {
        return $this->state(function (): array {
            $definition = ExamBank::TryoutSkd5->definition();

            return [
                'kind' => 'tryout',
                'bank_slug' => ExamBank::TryoutSkd5->value,
                'title' => $definition['title'],
                'question_snapshot' => $definition,
                'expires_at' => now()->addSeconds((int) $definition['durationSeconds']),
            ];
        });
    }

    public function passingTryout(): static
    {
        return $this->tryout()->state(function (array $attributes): array {
            $answers = [];
            foreach ($attributes['question_snapshot']['questions'] as $question) {
                $answers[(string) $question['id']] = $question['answer'];
            }

            return ['answers' => $answers];
        });
    }
}
