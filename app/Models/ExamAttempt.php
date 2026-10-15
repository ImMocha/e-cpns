<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExamBank;
use App\Enums\MaterialAccess;
use App\ExamScoring;
use Database\Factories\ExamAttemptFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'user_id', 'kind', 'bank_slug', 'title', 'status', 'question_snapshot',
    'answers', 'flags', 'current_index', 'started_at', 'expires_at',
    'submitted_at', 'score', 'max_score', 'passing_score', 'correct_count',
    'wrong_count', 'blank_count', 'weighted_answered_count', 'duration_seconds', 'auto_submitted', 'user_package_id',
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
            'weighted_answered_count' => 'integer',
            'duration_seconds' => 'integer',
            'auto_submitted' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function userPackage(): BelongsTo
    {
        return $this->belongsTo(UserPackage::class);
    }

    /** @param Builder<ExamAttempt> $query */
    public function scopeCompleted(Builder $query): void
    {
        $query->where('status', self::COMPLETED);
    }

    /** @param Builder<ExamAttempt> $query */
    public function scopeCertifiable(Builder $query): void
    {
        $query->completed()->where('kind', 'tryout')
            ->whereNotNull('submitted_at')->whereNotNull('score')->where('max_score', '>', 0)
            ->whereNotNull('passing_score')->whereColumn('score', '>=', 'passing_score');
    }

    public function canPrintCertificate(): bool
    {
        return $this->kind === 'tryout' && $this->status === self::COMPLETED
            && $this->submitted_at !== null && $this->score !== null && $this->max_score > 0
            && $this->passing_score !== null && $this->score >= $this->passing_score;
    }

    public function certificateNumber(): string
    {
        return 'GN-'.$this->submitted_at->format('Y').'-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
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
                $bank = Exam::query()->whereKey($bank->id)->sharedLock()->firstOrFail();
                abort_unless(Exam::query()->visibleToMembers()->whereKey($bank->id)->exists(), 404);
                $bank->load(['topic' => fn (Builder|Relation $topic): Builder|Relation => $topic->withLearningProgress($user)]);
                if (! $bank->topicIsReadyFor($user)) {
                    throw ValidationException::withMessages(['exam' => 'Selesaikan semua materi pada topik ini sebelum memulai latihan.']);
                }
            }
            $entitlement = null;
            if ($bank instanceof Exam && $bank->access_level === MaterialAccess::Premium && ! $user->isAdmin()) {
                $entitlements = $user->userPackages()->active()
                    ->whereHas('exams', fn (Builder $query) => $query->whereKey($bank->id))
                    ->orderBy('id')->lockForUpdate()->get();
                abort_if($entitlements->isEmpty(), 403, 'Sesi ini membutuhkan paket aktif yang memuatnya.');
                $entitlement = $entitlements->first(fn (UserPackage $item): bool => $bank->kind === 'quiz' || $item->hasTryoutQuota());
                if ($entitlement === null) {
                    throw ValidationException::withMessages(['exam' => 'Kuota tryout paket sudah habis. Pilih paket lain untuk memulai sesi baru.']);
                }
            }
            $definition = $bank->definition();
            $startedAt = now();
            $duration = (int) $definition['durationSeconds'];

            $attempt = self::create([
                'user_id' => $user->id,
                'user_package_id' => $entitlement?->id,
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
            if ($entitlement !== null && $bank->kind === 'tryout') {
                $entitlement->increment('tryouts_used');
            }

            return $attempt;
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

                return array_diff_key($question, array_flip(['answer', 'optionScores', 'explain', 'explainHtml', 'image_path', 'video_path']));
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
        $result = ExamScoring::evaluate($definition, $this->answers ?? []);
        $submittedAt = $this->isExpired() ? $this->expires_at->copy() : now();
        $rawMaximum = $result['max'];
        $rawScore = $result['score'];
        $percent = ($definition['scoreMode'] ?? 'points') === 'percent';

        $this->forceFill([
            'status' => self::COMPLETED,
            'submitted_at' => $submittedAt,
            'score' => $percent ? (int) round($rawScore / max(1, $rawMaximum) * 100) : $rawScore,
            'max_score' => $percent ? 100 : $rawMaximum,
            'passing_score' => $definition['passingScore'] ?? null,
            'correct_count' => $result['correct'],
            'wrong_count' => $result['wrong'],
            'blank_count' => $result['blank'],
            'weighted_answered_count' => $result['weighted'],
            'duration_seconds' => (int) max(0, $this->started_at->diffInSeconds($submittedAt)),
            'auto_submitted' => $automatic,
        ])->save();
    }

    /** @return array<string, array<string, int|string>> */
    public function sectionBreakdown(): array
    {
        $definition = $this->question_snapshot;
        $sections = ExamScoring::evaluate($definition, $this->answers ?? [])['sections'];

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
            'accuracy' => null, 'correct' => 0, 'wrong' => 0, 'blank' => 0, 'weighted' => 0,
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
            $summary['weighted'] += $attempt->weighted_answered_count;
            $summary['totalSeconds'] += $attempt->duration_seconds;

            foreach ($attempt->sectionBreakdown() as $code => $section) {
                $summary['sections'][$code] ??= ['section' => $code, 'total' => 0, 'correct' => 0, 'wrong' => 0, 'blank' => 0, 'weighted' => 0, 'weighted_total' => 0, 'score' => 0, 'max' => 0];
                foreach (['total', 'correct', 'wrong', 'blank', 'weighted', 'weighted_total', 'score', 'max'] as $field) {
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
            $summary['accuracy'] = $answered > 0 ? (int) round($summary['correct'] / $answered * 100) : ($summary['weighted'] > 0 ? null : 0);
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
