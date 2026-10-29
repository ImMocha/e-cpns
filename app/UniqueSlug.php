<?php

declare(strict_types=1);

namespace App;

use App\Enums\ExamBank;
use App\Models\Article;
use App\Models\Exam;
use App\Models\Material;
use App\Models\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use InvalidArgumentException;

class UniqueSlug
{
    public const RESOURCES = ['exams', 'articles', 'materials', 'sections'];

    public static function resolve(string $resource, string $value, ?int $ignoreId = null): string
    {
        [$model, $limit, $fallback] = match ($resource) {
            'exams' => [Exam::class, 80, 'sesi'],
            'articles' => [Article::class, 180, 'artikel'],
            'materials' => [Material::class, 200, 'materi'],
            'sections' => [Section::class, 140, 'bidang'],
            default => throw new InvalidArgumentException('Jenis alamat tidak dikenal.'),
        };
        $base = rtrim(substr(Str::slug($value) ?: $fallback, 0, $limit), '-');
        $candidate = $base;
        $suffix = 2;
        $reserved = $resource === 'exams' ? array_column(ExamBank::cases(), 'value') : [];
        while (in_array($candidate, $reserved, true) || $model::withTrashed()->where('slug', $candidate)
            ->when($ignoreId !== null, fn (Builder $query): Builder => $query->whereKeyNot($ignoreId))->exists()) {
            $ending = '-'.$suffix++;
            $candidate = rtrim(substr($base, 0, $limit - strlen($ending)), '-').$ending;
        }

        return $candidate;
    }
}
