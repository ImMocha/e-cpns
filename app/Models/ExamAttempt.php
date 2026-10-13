<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExamBank;
use App\Enums\QuestionStatus;
use Database\Factories\ExamAttemptFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

#[Fillable([
    'user_id', 'kind', 'bank_slug', 'title', 'status', 'question_snapshot',
    'answers', 'flags', 'current_index', 'started_at', 'expires_at',
    'submitted_at', 'score', 'max_score', 'passing_score', 'correct_count',
    'wrong_count', 'blank_count', 'duration_seconds', 'auto_submitted',
])]
class ExamAttempt extends Model
{
    protected $hidden = ['question_snapshot'];

    /** @use HasFactory<ExamAttemptFactory> */
    use HasFactory;

    public const IN_PROGRESS = 'in_progress';

    public const COMPLETED = 'completed';

    protected function casts(): array
    {
        return [
            'question_snapshot' => 'array',
            'answers' => 'array',
            'flags' => 'array',
            'current_index' => 'integer',
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'submitted_at' => 'datetime',
            'score' => 'integer',
            'max_score' => 'integer',
            'passing_score' => 'integer',
            'correct_count' => 'integer',
            'wrong_count' => 'integer',
            'blank_count' => 'integer',
            'duration_seconds' => 'integer',
            'auto_submitted' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @param Builder<ExamAttempt> $query */
    public function scopeCompleted(Builder $query): void
    {
        $query->where('status', self::COMPLETED);
    }

    public static function resumeOrStart(User $user, Exam|ExamBank $bank): self
    {
        return DB::transaction(function () use ($user, $bank): self {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            $attempt = self::query()
                ->whereBelongsTo($user)
                ->where('bank_slug', $bank instanceof Exam ? $bank->slug : $bank->value)
                ->where('kind', $bank instanceof Exam ? $bank->kind : $bank->kind())
                ->where('status', self::IN_PROGRESS)
                ->latest('id')
                ->first();

            if ($attempt?->isExpired()) {
                $attempt->complete(true);
                $attempt = null;
            }

            if ($attempt !== null) {
                return $attempt;
            }

            if ($bank instanceof Exam) {
                abort_unless($bank->status === QuestionStatus::Published, 404);
            }
            $definition = $bank->definition();
            $startedAt = now();
            $duration = (int) $definition['durationSeconds'];

            return self::create([
                'user_id' => $user->id,
                'kind' => $bank instanceof Exam ? $bank->kind : $bank->kind(),
                'bank_slug' => $bank instanceof Exam ? $bank->slug : $bank->value,
                'title' => $definition['title'],
                'status' => self::IN_PROGRESS,
                'question_snapshot' => $definition,
                'answers' => [],
                'flags' => [],
                'started_at' => $startedAt,
                'expires_at' => $duration > 0 ? $startedAt->copy()->addSeconds($duration) : null,
            ]);
        });
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->lessThanOrEqualTo(now());
    }

    /** @return array<string, mixed> */
    public function clientConfig(): array
    {
        $definition = $this->question_snapshot;
        unset($definition['mediaPaths']);
        $definition['questions'] = array_map(
            function (array $question): array {
                foreach (['image', 'video'] as $media) {
                    if ($question[$media.'_path'] ?? null) {
                        $question[$media.'Url'] = route('exam-attempts.media', ['examAttempt' => $this->id, 'question' => $question['id'], 'media' => $media]);
                    }
                }

                return array_diff_key($question, array_flip(['answer', 'explain', 'explainHtml', 'image_path', 'video_path']));
            },
            $definition['questions'],
        );
        $definition['attemptId'] = $this->id;
        $definition['kind'] = $this->kind;
        $definition['saveUrl'] = route('exam-attempts.save', $this);
        $definition['submitUrl'] = route('exam-attempts.submit', $this);
        $definition['exitUrl'] = $this->kind === 'quiz' ? route('quizzes.index') : route('tryouts.index');
        $definition['serverNow'] = now()->getTimestampMs();
        $definition['serverUpdatedAt'] = $this->updated_at->getTimestampMs();
        $definition['expiresAt'] = $this->expires_at?->getTimestampMs();
        $definition['state'] = [
            'answers' => $this->answers ?? [],
            'flags' => $this->flags ?? [],
            'current' => $this->current_index,
        ];

        return $definition;
    }

    public function complete(bool $automatic = false): void
    {
        DB::transaction(function () use ($automatic): void {
            $attempt = self::query()->whereKey($this->id)->lockForUpdate()->firstOrFail();
            $attempt->completeSnapshot($automatic || $attempt->isExpired());
        });
        $this->refresh();
    }

    private function completeSnapshot(bool $automatic): void
    {
        if ($this->status === self::COMPLETED) {
            return;
        }

        $definition = $this->question_snapshot;
        $answers = $this->answers ?? [];
        $correct = 0;
        $wrong = 0;
        $blank = 0;

        foreach ($definition['questions'] as $question) {
            $answer = $answers[(string) $question['id']] ?? null;

            if ($answer === null) {
                $blank++;
            } elseif ((int) $answer === (int) $question['answer']) {
                $correct++;
            } else {
                $wrong++;
            }
        }

        $points = (int) $definition['pointsPerCorrect'];
        $penalty = (int) $definition['penaltyWrong'];
        $submittedAt = $this->isExpired() ? $this->expires_at->copy() : now();
        $rawMaximum = count($definition['questions']) * $points;
        $rawScore = max(0, $correct * $points - $wrong * $penalty);
        $percent = ($definition['scoreMode'] ?? 'points') === 'percent';

        $this->forceFill([
            'status' => self::COMPLETED,
            'submitted_at' => $submittedAt,
            'score' => $percent ? (int) round($rawScore / max(1, $rawMaximum) * 100) : $rawScore,
            'max_score' => $percent ? 100 : $rawMaximum,
            'passing_score' => $definition['passingScore'] ?? null,
            'correct_count' => $correct,
            'wrong_count' => $wrong,
            'blank_count' => $blank,
            'duration_seconds' => (int) max(0, $this->started_at->diffInSeconds($submittedAt)),
            'auto_submitted' => $automatic,
        ])->save();
    }

    /** @return array<string, array{section: string, total: int, correct: int, wrong: int, blank: int, score: int, max: int}> */
    public function sectionBreakdown(): array
    {
        $definition = $this->question_snapshot;
        $answers = $this->answers ?? [];
        $points = (int) $definition['pointsPerCorrect'];
        $penalty = (int) $definition['penaltyWrong'];
        $sections = [];

        foreach ($definition['questions'] as $question) {
            $code = $question['section'] ?? 'Umum';
            $sections[$code] ??= ['section' => $code, 'total' => 0, 'correct' => 0, 'wrong' => 0, 'blank' => 0, 'score' => 0, 'max' => 0];
            $sections[$code]['total']++;
            $sections[$code]['max'] += $points;
            $answer = $answers[(string) $question['id']] ?? null;

            if ($answer === null) {
                $sections[$code]['blank']++;
            } elseif ((int) $answer === (int) $question['answer']) {
                $sections[$code]['correct']++;
                $sections[$code]['score'] += $points;
            } else {
                $sections[$code]['wrong']++;
                $sections[$code]['score'] -= $penalty;
            }
        }

        foreach ($sections as &$section) {
            $section['score'] = max(0, $section['score']);
            if (($definition['scoreMode'] ?? 'points') === 'percent') {
                $section['score'] = (int) round($section['score'] / max(1, $section['max']) * 100);
                $section['max'] = 100;
            }
        }

        return $sections;
    }

    /**
     * Ringkasan personal. Cursor membatasi pemakaian memori saat riwayat bertambah.
     *
     * @return array<string, mixed>
     */
    public static function summaryFor(User $user, ?string $kind = null, ?DateTimeInterface $since = null): array
    {
        $query = self::query()->whereBelongsTo($user)->completed()
            ->when($kind, fn (Builder $query) => $query->where('kind', $kind))
            ->when($since, fn (Builder $query) => $query->where('submitted_at', '>=', $since))
            ->orderByDesc('submitted_at')->orderByDesc('id');

        $summary = [
            'count' => 0, 'tryoutCount' => 0, 'quizCount' => 0,
            'latest' => null, 'highest' => null, 'averageScore' => null,
            'accuracy' => null, 'correct' => 0, 'wrong' => 0, 'blank' => 0,
            'totalSeconds' => 0, 'sections' => [], 'recent' => [], 'trend' => [],
        ];
        $scoreTotal = 0;

        foreach ($query->cursor() as $attempt) {
            $summary['count']++;
            $summary[$attempt->kind.'Count']++;
            $summary['latest'] ??= $attempt;

            if ($summary['highest'] === null || $attempt->score > $summary['highest']->score) {
                $summary['highest'] = $attempt;
            }

            $scoreTotal += $attempt->score;
            $summary['correct'] += $attempt->correct_count;
            $summary['wrong'] += $attempt->wrong_count;
            $summary['blank'] += $attempt->blank_count;
            $summary['totalSeconds'] += $attempt->duration_seconds;

            foreach ($attempt->sectionBreakdown() as $code => $section) {
                $summary['sections'][$code] ??= ['section' => $code, 'total' => 0, 'correct' => 0, 'wrong' => 0, 'blank' => 0, 'score' => 0, 'max' => 0];
                foreach (['total', 'correct', 'wrong', 'blank', 'score', 'max'] as $field) {
                    $summary['sections'][$code][$field] += $section[$field];
                }
            }

            if (count($summary['recent']) < 8) {
                $summary['recent'][] = $attempt;
            }
        }

        if ($summary['count'] > 0) {
            $summary['averageScore'] = (int) round($scoreTotal / $summary['count']);
            $answered = $summary['correct'] + $summary['wrong'];
            $summary['accuracy'] = $answered > 0 ? (int) round($summary['correct'] / $answered * 100) : 0;
            $summary['trend'] = array_reverse($summary['recent']);
        }

        return $summary;
    }

    public static function finalizeExpiredFor(User $user): void
    {
        self::query()->whereBelongsTo($user)
            ->where('status', self::IN_PROGRESS)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->pluck('id')
            ->each(function (int $attemptId): void {
                DB::transaction(function () use ($attemptId): void {
                    $attempt = self::query()->lockForUpdate()->find($attemptId);

                    if ($attempt?->status === self::IN_PROGRESS && $attempt->isExpired()) {
                        $attempt->complete(true);
                    }
                });
            });
    }

    public function attemptUrl(): string
    {
        return route($this->kind === 'quiz' ? 'quizzes.attempt' : 'tryouts.attempt',
            [$this->kind === 'quiz' ? 'quiz' : 'tryout' => $this->bank_slug, 'attempt' => $this->id]);
    }

    public function resultUrl(): string
    {
        $route = $this->kind === 'quiz' ? 'quizzes.result' : 'tryouts.result';
        $parameter = $this->kind === 'quiz' ? 'quiz' : 'tryout';

        return route($route, [$parameter => $this->bank_slug, 'attempt' => $this->id]);
    }
}
