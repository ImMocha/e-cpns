<?php

declare(strict_types=1);

namespace App\Models;

use App\ArticleContent;
use App\Enums\QuestionStatus;
use Database\Factories\ExamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

#[Fillable(['kind', 'slug', 'title', 'description', 'question_count', 'duration_minutes', 'points_per_correct', 'penalty_wrong', 'passing_score', 'random_questions', 'random_options', 'status', 'created_by', 'updated_by'])]
class Exam extends Model
{
    /** @use HasFactory<ExamFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['question_count' => 'integer', 'duration_minutes' => 'integer', 'points_per_correct' => 'integer',
            'penalty_wrong' => 'integer', 'passing_score' => 'integer', 'random_questions' => 'boolean',
            'random_options' => 'boolean', 'status' => QuestionStatus::class];
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class);
    }

    /** @param Builder<Exam> $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', QuestionStatus::Published);
    }

    public function maximumScore(): int
    {
        return $this->kind === 'quiz' ? 100 : $this->question_count * $this->points_per_correct;
    }

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $questions = $this->questions()->visibleToMembers()->with('section');
        if ($this->random_questions) {
            $questions->inRandomOrder();
        } else {
            $questions->orderBy('questions.id');
        }
        $selected = $questions->limit($this->question_count)->get();
        if ($selected->count() < $this->question_count) {
            throw ValidationException::withMessages(['exam' => 'Soal terbit pada sesi ini belum mencukupi. Silakan pilih sesi lain atau hubungi admin.']);
        }

        return [
            'id' => $this->slug, 'title' => $this->title, 'subtitle' => $this->description,
            'durationSeconds' => $this->duration_minutes * 60, 'showTimer' => $this->duration_minutes > 0,
            'pointsPerCorrect' => $this->points_per_correct, 'penaltyWrong' => $this->penalty_wrong,
            'passingScore' => $this->passing_score, 'scoreMode' => $this->kind === 'quiz' ? 'percent' : 'points',
            'randomQuestions' => $this->random_questions, 'randomOptions' => $this->random_options,
            'mediaPaths' => $selected->flatMap(fn (Question $question): array => [$question->image_path, $question->video_path])->filter()->values()->all(),
            'questions' => $selected->map(function (Question $question): array {
                $indices = collect(array_keys($question->options));
                if ($this->random_options) {
                    $indices = $indices->shuffle();
                }
                $indices = $indices->values();

                return ['id' => $question->id, 'section' => $question->section->code, 'topic' => $question->title,
                    'stem' => ArticleContent::text($question->safeBody()), 'stemHtml' => $question->safeBody(),
                    'options' => $indices->map(fn (int $index): string => $question->options[$index])->all(),
                    'answer' => $indices->search($question->correct_option, true),
                    'explain' => ArticleContent::text($question->safeExplanation()), 'explainHtml' => $question->safeExplanation(),
                    'image_path' => $question->image_path, 'video_path' => $question->video_path];
            })->all(),
        ];
    }
}
