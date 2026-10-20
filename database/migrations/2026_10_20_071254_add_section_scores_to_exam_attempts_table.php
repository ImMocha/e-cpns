<?php

use App\ExamScoring;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table): void {
            $table->json('section_scores')->nullable();
        });

        DB::table('exam_attempts')->where('status', 'completed')
            ->select(['id', 'question_snapshot', 'answers'])->chunkById(100, function (Collection $attempts): void {
                foreach ($attempts as $attempt) {
                    $definition = json_decode($attempt->question_snapshot, true, 512, JSON_THROW_ON_ERROR);
                    $answers = json_decode($attempt->answers ?? '[]', true, 512, JSON_THROW_ON_ERROR);
                    $sections = ExamScoring::evaluate($definition, $answers)['sections'];
                    DB::table('exam_attempts')->where('id', $attempt->id)->update([
                        'section_scores' => json_encode($sections, JSON_THROW_ON_ERROR),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table): void {
            $table->dropColumn('section_scores');
        });
    }
};
