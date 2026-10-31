<?php

declare(strict_types=1);

namespace App\Models;

use App\ArticleContent;
use App\Enums\MaterialAccess;
use App\Enums\QuestionScoring;
use App\Enums\QuestionStatus;
use Database\Factories\ExamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

#[Fillable(['kind', 'slug', 'title', 'description', 'cover_path', 'question_count', 'duration_minutes', 'points_per_correct', 'penalty_wrong', 'passing_score', 'random_questions', 'random_options', 'status', 'access_level', 'topic_id', 'requires_topic_completion', 'created_by', 'updated_by', 'free_claim_enabled', 'free_claim_terms', 'free_claim_instagram_url'])]
class Exam extends Model
{
    /** @use HasFactory<ExamFactory> */
    use HasFactory, SoftDeletes;

    protected $attributes = ['access_level' => 'premium'];

    protected function casts(): array
    {
        return ['question_count' => 'integer', 'duration_minutes' => 'integer', 'points_per_correct' => 'integer',
            'penalty_wrong' => 'integer', 'passing_score' => 'integer', 'random_questions' => 'boolean',
            'random_options' => 'boolean', 'status' => QuestionStatus::class, 'access_level' => MaterialAccess::class,
            'requires_topic_completion' => 'boolean', 'free_claim_enabled' => 'boolean'];
    }

    public function freeClaimTermsVersion(): string
    {
        return hash('sha256', ($this->free_claim_terms ?? '').'|'.($this->free_claim_instagram_url ?? ''));
    }

    public function accessLabel(): string
    {
        return $this->free_claim_enabled ? 'Gratis Bersyarat' : $this->access_level->label();
    }

    public function coverUrl(): string
    {
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path)
            : asset('images/'.($this->kind === 'tryout' ? 'tryout-card-cover.webp' : 'practice-card-cover.webp'));
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function topicIsReadyFor(User $user): bool
    {
        return $user->isAdmin() || ($this->kind === 'tryout' && ! $user->canAccessLearningFeatures()) || ! $this->requires_topic_completion || $this->topic?->hasCompletedMaterials() === true;
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class)->withPivot('position')
            ->orderByRaw('exam_question.position IS NULL')->orderByPivot('position')->orderBy('questions.id');
    }

    public function userPackages(): BelongsToMany
    {
        return $this->belongsToMany(UserPackage::class);
    }

    public function scoringQuestions(): BelongsToMany
    {
        return $this->questions()->visibleToMembers()->select(['questions.id', 'questions.scoring_type']);
    }

    /** @param Builder<Exam> $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', QuestionStatus::Published);
    }

    /** @param Builder<Exam> $query */
    public function scopeAvailableForTokens(Builder $query): void
    {
        $query->visibleToMembers()->where('kind', 'tryout')->where('access_level', MaterialAccess::Premium)
            ->where('free_claim_enabled', false)
            ->where('question_count', '<=', Question::query()->visibleToMembers()
                ->join('exam_question', 'questions.id', '=', 'exam_question.question_id')
                ->whereColumn('exam_question.exam_id', 'exams.id')->selectRaw('count(*)'));
    }

    /** @param Builder<Exam> $query */
    public function scopeVisibleToMembers(Builder $query): void
    {
        $query->published()->where(function (Builder $query): void {
            $query->whereNull('topic_id')->orWhereHas('topic', fn (Builder $topic): Builder => $topic->active()
                ->whereHas('section', fn (Builder $section): Builder => $section->active()));
        });
    }

    public function maximumScore(): int
    {
        if (! $this->random_questions) {
            $questions = $this->relationLoaded('scoringQuestions') ? $this->scoringQuestions : $this->scoringQuestions()->limit($this->question_count)->get();

            return $questions->take($this->question_count)
                ->sum(fn (Question $question): int => $question->isWeighted() ? 5 : $this->points_per_correct);
        }
        $weighted = $this->weighted_available_count ?? $this->questions()->visibleToMembers()->where('scoring_type', QuestionScoring::Weighted)->count();
        $available = $this->available_count ?? $this->questions()->visibleToMembers()->count();
        $weightedSlots = $this->points_per_correct <= 5
            ? min($weighted, $this->question_count)
            : max(0, $this->question_count - ($available - $weighted));

        return $weightedSlots * 5 + ($this->question_count - $weightedSlots) * $this->points_per_correct;
    }

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $questions = $this->questions()->visibleToMembers()->with('section');
        if ($this->random_questions) {
            $questions->reorder()->inRandomOrder();
        }
        $selected = $questions->limit($this->question_count)->get();
        if ($selected->count() < $this->question_count) {
            throw ValidationException::withMessages(['exam' => 'Soal terbit pada sesi ini belum mencukupi. Silakan pilih sesi lain atau hubungi admin.']);
        }

        return [
            'id' => $this->slug, 'title' => $this->title, 'subtitle' => $this->description,
            'durationSeconds' => $this->duration_minutes * 60, 'showTimer' => $this->duration_minutes > 0,
            'pointsPerCorrect' => $this->points_per_correct, 'penaltyWrong' => $this->penalty_wrong,
            'passingScore' => $this->passing_score, 'scoreMode' => 'points',
            'randomQuestions' => $this->random_questions, 'randomOptions' => $this->random_options,
            'mediaPaths' => $selected->flatMap(fn (Question $question): array => [$question->image_path, $question->video_path,
                $question->explanation_image_path, ...($question->option_image_paths ?? [])])->filter()->values()->all(),
            'questions' => $selected->map(function (Question $question): array {
                $indices = collect(array_keys($question->options));
                if ($this->random_options && $question->shuffle_options) {
                    $indices = $indices->shuffle();
                }
                $indices = $indices->values();

                return ['id' => $question->id, 'section' => $question->section->code, 'topic' => $question->title,
                    'stem' => ArticleContent::text($question->safeBody()), 'stemHtml' => $question->safeBody(),
                    'options' => $indices->map(fn (int $index): string => $question->options[$index])->all(),
                    'option_image_paths' => $indices->map(fn (int $index): ?string => $question->option_image_paths[$index] ?? null)->all(),
                    'scoringType' => $question->scoring_type->value,
                    'fixedOptionOrder' => ! $question->shuffle_options,
                    'optionScores' => $question->isWeighted() ? $indices->map(fn (int $index): int => (int) $question->option_scores[$index])->all() : null,
                    'answer' => $question->isWeighted() ? null : $indices->search($question->correct_option, true),
                    'explain' => ArticleContent::text($question->safeExplanation()), 'explainHtml' => $question->safeExplanation(),
                    'image_path' => $question->image_path, 'video_path' => $question->video_path,
                    'explanation_image_path' => $question->explanation_image_path];
            })->all(),
        ];
    }
}
