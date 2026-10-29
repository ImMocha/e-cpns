<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\MaterialAccess;
use App\Enums\QuestionStatus;
use App\Models\Exam;
use App\Models\Question;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PracticeSessionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->definitions() as $definition) {
            if (Exam::withTrashed()->where('slug', $definition['slug'])->exists()) {
                continue;
            }
            $ids = [];
            foreach ($definition['sections'] as $code => [$count, $offset]) {
                $sectionIds = Question::query()->visibleToMembers()
                    ->where('source_key', 'like', SessionQuestionSeeder::SOURCE_PREFIX.$code.':%')
                    ->whereHas('section', fn (Builder $section): Builder => $section->where('code', $code))
                    ->orderBy('source_key')->offset($offset)->limit($count)->pluck('id')->all();
                if (count($sectionIds) !== $count) {
                    $this->command?->warn($definition['title'].' belum dibuat karena soal terbit '.$code.' belum mencukupi.');

                    continue 2;
                }
                $ids = [...$ids, ...$sectionIds];
            }
            DB::transaction(function () use ($definition, $ids): void {
                $composition = implode(', ', array_map(fn (string $code, array $selection): string => $selection[0].' '.$code,
                    array_keys($definition['sections']), array_values($definition['sections'])));
                $exam = Exam::withTrashed()->firstOrCreate(['slug' => $definition['slug']], [
                    'kind' => 'practice', 'title' => $definition['title'],
                    'description' => 'Latihan mandiri berisi '.$composition.'. Bersumber dari bank soal contoh SKD dengan kunci dan pembahasan setelah selesai.',
                    'topic_id' => null, 'requires_topic_completion' => false,
                    'question_count' => count($ids), 'duration_minutes' => $definition['minutes'],
                    'points_per_correct' => 5, 'penalty_wrong' => 0, 'passing_score' => null,
                    'random_questions' => true, 'random_options' => true,
                    'access_level' => MaterialAccess::Premium, 'status' => QuestionStatus::Published,
                ]);
                if ($exam->wasRecentlyCreated) {
                    $exam->questions()->sync(collect($ids)->mapWithKeys(fn (int $id, int $position): array => [$id => ['position' => $position + 1]])->all());
                }
            });
        }
    }

    /** @return list<array{slug: string, title: string, minutes: int, sections: array<string, array{int, int}>}> */
    private function definitions(): array
    {
        return [
            ['slug' => 'contoh-latihan-skd-campuran-01', 'title' => 'Contoh: Latihan Campuran SKD 01', 'minutes' => 30,
                'sections' => ['TWK' => [10, 0], 'TIU' => [10, 0], 'TKP' => [10, 0]]],
            ['slug' => 'contoh-latihan-skd-campuran-02', 'title' => 'Contoh: Latihan Campuran SKD 02', 'minutes' => 30,
                'sections' => ['TWK' => [10, 10], 'TIU' => [10, 10], 'TKP' => [10, 10]]],
            ['slug' => 'contoh-latihan-skd-lengkap', 'title' => 'Contoh: Latihan SKD Semua Bidang', 'minutes' => 100,
                'sections' => ['TWK' => [30, 0], 'TIU' => [35, 0], 'TKP' => [45, 0]]],
            ['slug' => 'contoh-latihan-twk', 'title' => 'Contoh: Latihan Khusus TWK', 'minutes' => 30, 'sections' => ['TWK' => [30, 0]]],
            ['slug' => 'contoh-latihan-tiu', 'title' => 'Contoh: Latihan Khusus TIU', 'minutes' => 35, 'sections' => ['TIU' => [35, 0]]],
            ['slug' => 'contoh-latihan-tkp', 'title' => 'Contoh: Latihan Khusus TKP', 'minutes' => 40, 'sections' => ['TKP' => [45, 0]]],
        ];
    }
}
