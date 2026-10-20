<?php

use App\ExamScoring;
use App\Models\Exam;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            Exam::withTrashed()->where('kind', 'quiz')->whereNotNull('passing_score')
                ->chunkById(100, function (Collection $exams): void {
                    foreach ($exams as $exam) {
                        DB::table('exams')->where('id', $exam->id)->update([
                            'passing_score' => $this->pointThreshold($exam->passing_score, $exam->maximumScore()),
                        ]);
                    }
                });

            DB::table('exam_attempts')->select(['id', 'status', 'question_snapshot', 'answers'])
                ->chunkById(100, function (Collection $attempts): void {
                    foreach ($attempts as $attempt) {
                        $definition = json_decode($attempt->question_snapshot, true, 512, JSON_THROW_ON_ERROR);
                        if (($definition['scoreMode'] ?? 'points') !== 'percent') {
                            continue;
                        }

                        $result = ExamScoring::evaluate($definition, json_decode($attempt->answers ?? '[]', true, 512, JSON_THROW_ON_ERROR));
                        $definition['scoreMode'] = 'points';
                        $definition['passingScore'] = $this->pointThreshold($definition['passingScore'] ?? null, $result['max']);
                        $values = ['question_snapshot' => json_encode($definition, JSON_THROW_ON_ERROR)];

                        if ($attempt->status === 'completed') {
                            $values += ['score' => $result['score'], 'max_score' => $result['max'],
                                'passing_score' => $definition['passingScore']];
                        }

                        DB::table('exam_attempts')->where('id', $attempt->id)->update($values);
                    }
                });
        });
    }

    public function down(): void
    {
        throw new LogicException('Konversi skor poin tidak dapat dibalik tanpa cadangan nilai ambang sebelumnya.');
    }

    /** Preserve the passing boundary of the previously rounded percentage. */
    private function pointThreshold(?int $threshold, int $maximum): ?int
    {
        return $threshold === null ? null : (int) ceil(max(0, $threshold - 0.5) * $maximum / 100);
    }
};
