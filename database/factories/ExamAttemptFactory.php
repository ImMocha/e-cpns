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
            $definition = $attributes['question_snapshot'];
            $templates = collect($definition['questions'])->keyBy('section');
            $questions = [];
            $answers = [];
            foreach (['TWK' => 13, 'TIU' => 16, 'TKP' => 34] as $code => $count) {
                for ($index = 0; $index < $count; $index++) {
                    $question = $templates[$code];
                    $question['id'] = count($questions) + 1;
                    if ($code === 'TKP') {
                        $question['scoringType'] = 'weighted';
                        $question['optionScores'] = [1, 2, 3, 4, 5];
                        $question['answer'] = null;
                    }
                    $questions[] = $question;
                    $answers[(string) $question['id']] = $code === 'TKP' ? ($index === 33 ? 0 : 4) : $question['answer'];
                }
            }
            $definition['questions'] = $questions;
            $definition['passingScore'] = null;

            return ['question_snapshot' => $definition, 'answers' => $answers];
        });
    }
}
