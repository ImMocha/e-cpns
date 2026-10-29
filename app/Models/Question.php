<?php

declare(strict_types=1);

namespace App\Models;

use App\ArticleContent;
use App\Enums\QuestionDifficulty;
use App\Enums\QuestionScoring;
use App\Enums\QuestionStatus;
use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['section_id', 'title', 'body', 'options', 'correct_option', 'scoring_type', 'option_scores', 'shuffle_options', 'source_key', 'explanation', 'image_path', 'video_path', 'difficulty', 'status', 'published_at', 'created_by', 'updated_by'])]
class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory, SoftDeletes;

    protected $hidden = ['correct_option', 'option_scores', 'explanation'];

    protected $attributes = ['scoring_type' => 'binary', 'shuffle_options' => true];

    protected static function booted(): void
    {
        static::saving(function (Question $question): void {
            foreach (['body', 'explanation'] as $attribute) {
                if ($question->isDirty($attribute)) {
                    $question->{$attribute} = ArticleContent::sanitize($question->{$attribute});
                }
            }
        });
    }

    protected function casts(): array
    {
        return ['options' => 'array', 'option_scores' => 'array', 'correct_option' => 'integer', 'scoring_type' => QuestionScoring::class,
            'shuffle_options' => 'boolean', 'difficulty' => QuestionDifficulty::class,
            'status' => QuestionStatus::class, 'published_at' => 'datetime'];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function exams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class);
    }

    /** @param Builder<Question> $query */
    public function scopeVisibleToMembers(Builder $query): void
    {
        $query->where('status', QuestionStatus::Published->value)->whereNotNull('published_at')
            ->where('published_at', '<=', now())->whereHas('section', fn (Builder $section) => $section->active());
    }

    /** @param Builder<Question> $query */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        if (trim((string) $term) !== '') {
            $query->where(fn (Builder $search) => $search->where('title', 'like', '%'.$term.'%')->orWhere('body', 'like', '%'.$term.'%'));
        }
    }

    public function safeBody(): string
    {
        return ArticleContent::sanitize($this->body);
    }

    public function safeExplanation(): string
    {
        return ArticleContent::sanitize($this->explanation);
    }

    public function isWeighted(): bool
    {
        return $this->scoring_type === QuestionScoring::Weighted;
    }
}
