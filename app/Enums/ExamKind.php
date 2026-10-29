<?php

declare(strict_types=1);

namespace App\Enums;

enum ExamKind: string
{
    case Tryout = 'tryout';
    case Quiz = 'quiz';
    case Practice = 'practice';

    public function label(): string
    {
        return match ($this) {
            self::Tryout => 'Tryout',
            self::Quiz => 'Latihan Topik',
            self::Practice => 'Latihan Soal',
        };
    }

    public function routePrefix(): string
    {
        return match ($this) {
            self::Tryout => 'tryouts',
            self::Quiz => 'quizzes',
            self::Practice => 'practices',
        };
    }

    public function catalogRoute(): string
    {
        return match ($this) {
            self::Tryout => 'tryouts.index',
            self::Quiz => 'quizzes.topics',
            self::Practice => 'quizzes.index',
        };
    }

    public function adminRoutePrefix(): string
    {
        return match ($this) {
            self::Tryout => 'admin.exams',
            self::Quiz => 'admin.materials.quizzes',
            self::Practice => 'admin.practices',
        };
    }
}
