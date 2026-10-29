<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\Question;
use Illuminate\Database\Seeder;

class ExamSeeder extends Seeder
{
    public function run(): void
    {
        $ids = Question::query()->visibleToMembers()->where('title', 'like', 'Contoh:%')->orderBy('id')->limit(3)->pluck('id');
        if ($ids->isEmpty()) {
            return;
        }
        foreach (['quiz' => 'latihan-bank-soal', 'tryout' => 'simulasi-bank-soal'] as $kind => $slug) {
            $exam = Exam::withTrashed()->firstOrCreate(['slug' => $slug], ['kind' => $kind, 'access_level' => 'premium',
                'title' => $kind === 'quiz' ? 'Contoh: Latihan Soal' : 'Contoh: Simulasi CAT',
                'description' => 'Sesi contoh dari soal terbit. Admin dapat mengatur isi, jumlah soal, durasi, dan penilaiannya.',
                'status' => 'published', 'question_count' => $ids->count(), 'duration_minutes' => $kind === 'quiz' ? 0 : 10,
                'points_per_correct' => 5, 'penalty_wrong' => 0, 'random_questions' => true, 'random_options' => true]);
            if ($exam->wasRecentlyCreated) {
                $exam->questions()->sync($ids);
            }
        }
    }
}
