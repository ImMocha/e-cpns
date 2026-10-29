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

class TryoutSessionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['a' => 'A', 'b' => 'B'] as $key => $label) {
            $slug = 'contoh-tryout-skd-'.$key;
            if (Exam::withTrashed()->where('slug', $slug)->exists()) {
                continue;
            }
            $ids = [];
            foreach (['TWK' => 30, 'TIU' => 35, 'TKP' => 45] as $code => $count) {
                $sectionIds = Question::query()->visibleToMembers()
                    ->where('source_key', 'like', SessionQuestionSeeder::SOURCE_PREFIX.$code.':%')
                    ->whereHas('section', fn (Builder $section): Builder => $section->where('code', $code))
                    ->orderBy('source_key')->pluck('id')->all();
                if (count($sectionIds) !== $count) {
                    $this->command?->warn('Tryout contoh '.$label.' belum dibuat karena bank soal '.$code.' belum lengkap.');

                    continue 2;
                }
                if ($key === 'b') {
                    $offset = intdiv($count, 2);
                    $sectionIds = [...array_slice($sectionIds, $offset), ...array_slice($sectionIds, 0, $offset)];
                }
                $ids = [...$ids, ...$sectionIds];
            }
            DB::transaction(function () use ($slug, $label, $ids): void {
                $exam = Exam::withTrashed()->firstOrCreate(['slug' => $slug], [
                    'kind' => 'tryout', 'title' => 'Contoh: Tryout SKD '.$label,
                    'description' => 'Simulasi 30 TWK, 35 TIU, dan 45 TKP dalam 100 menit. Tryout A dan B memakai bank soal contoh yang sama dengan urutan berbeda. Bukan soal resmi seleksi.',
                    'question_count' => 110, 'duration_minutes' => 100, 'points_per_correct' => 5, 'penalty_wrong' => 0,
                    'random_questions' => false, 'random_options' => true,
                    'access_level' => MaterialAccess::Premium, 'status' => QuestionStatus::Published,
                ]);
                if ($exam->wasRecentlyCreated) {
                    $exam->questions()->sync(collect($ids)->mapWithKeys(fn (int $id, int $position): array => [$id => ['position' => $position + 1]])->all());
                }
            });
        }
    }
}
