<?php

namespace App\Enums;

use JsonException;

enum ExamBank: string
{
    case QuizSilogisme = 'latihan-1';
    case TryoutSkd5 = 'skd-5';

    public function kind(): string
    {
        return match ($this) {
            self::QuizSilogisme => 'quiz',
            self::TryoutSkd5 => 'tryout',
        };
    }

    public static function findFor(string $kind, string $slug): ?self
    {
        $bank = self::tryFrom($slug);

        return $bank?->kind() === $kind ? $bank : null;
    }

    /** @return array<int, self> */
    public static function forKind(string $kind): array
    {
        return array_values(array_filter(self::cases(), fn (self $bank): bool => $bank->kind() === $kind));
    }

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $path = resource_path('js/lontar/data/bank-'.$this->value.'.json');

        try {
            return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new \RuntimeException('Bank soal tidak dapat dibaca: '.$this->value, previous: $exception);
        }
    }

    /** @return array<string, mixed> */
    public function publicDefinition(): array
    {
        $definition = $this->definition();
        $definition['questions'] = array_map(
            fn (array $question): array => array_diff_key($question, array_flip(['answer', 'explain'])),
            $definition['questions'],
        );

        return $definition;
    }
}
