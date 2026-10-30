<?php

declare(strict_types=1);

namespace App;

use App\Enums\MaterialAccess;
use App\Enums\PackageTier;
use App\Enums\QuestionScoring;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamClaim;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

class ExamCatalog
{
    /** @return array<string, Collection<int, array{exam: Exam, hasAccess: bool}>> */
    public function featured(?User $user): array
    {
        $entitlements = $user?->userPackages()->active()->with('exams')->get() ?? collect();
        $hasPremium = $entitlements->contains(fn (UserPackage $package): bool => $package->tier === PackageTier::Premium);
        $approvedClaims = $user ? ExamClaim::query()->whereBelongsTo($user)->where('status', 'approved')->pluck('exam_id') : collect();
        $learningAllowed = $user?->canAccessLearningFeatures() === true;
        $featured = [];

        foreach (['tryout', 'practice'] as $kind) {
            $featured[$kind] = Exam::query()->visibleToMembers()->where('kind', $kind)
                ->with('topic.section')->latest('id')->limit(3)->get()
                ->map(function (Exam $exam) use ($user, $entitlements, $hasPremium, $approvedClaims, $learningAllowed): array {
                    $hasAccess = $user?->isAdmin() === true || ($exam->free_claim_enabled
                        ? $hasPremium || $approvedClaims->contains($exam->id)
                        : $exam->access_level === MaterialAccess::Free || $entitlements->contains(
                            fn (UserPackage $package): bool => $package->tier === PackageTier::Premium
                                || (($exam->kind === 'tryout' || $package->tier === null) && $package->exams->contains('id', $exam->id)),
                        ));

                    return ['exam' => $exam, 'hasAccess' => $hasAccess && ($exam->kind === 'tryout' || $learningAllowed)];
                });
        }

        return $featured;
    }

    /**
     * @param  list<int>|null  $topicIds
     * @return array<string, mixed>
     */
    public function listing(User $user, string $kind, ?array $topicIds = null): array
    {
        ExamAttempt::finalizeExpiredFor($user);
        $attempts = ExamAttempt::query()->whereBelongsTo($user)->where('kind', $kind)
            ->select(['id', 'bank_slug', 'kind', 'title', 'status', 'answers', 'score', 'max_score', 'submitted_at', 'expires_at'])
            ->latest('id')->get()->groupBy('bank_slug');
        $entitlements = $user->userPackages()->active()->with('exams')->get();
        $hasPremium = $entitlements->contains(fn (UserPackage $package): bool => $package->tier === PackageTier::Premium);
        $learningAllowed = $user->canAccessLearningFeatures();
        $tokenPackages = $kind === 'tryout' && ! $user->isAdmin()
            ? $entitlements->filter(fn (UserPackage $item): bool => $item->tryout_tokens > 0 && $item->hasTryoutQuota())->sortBy('id')
            : collect();
        $tokenExamIds = $tokenPackages->isNotEmpty()
            ? array_fill_keys(Exam::query()->availableForTokens()->pluck('exams.id')->all(), true) : [];
        $claims = ExamClaim::query()->whereBelongsTo($user)->select(['id', 'exam_id', 'status', 'review_note'])
            ->latest('id')->get()->unique('exam_id')->keyBy('exam_id');

        $exams = Exam::query()->visibleToMembers()->where('kind', $kind)
            ->when($topicIds !== null, fn (Builder $query): Builder => $query->whereIn('topic_id', $topicIds))
            ->with(['topic' => fn (Builder|Relation $topic): Builder|Relation => $topic->withLearningProgress($user)])
            ->withCount(['questions as available_count' => fn (Builder $query) => $query->visibleToMembers(),
                'questions as weighted_available_count' => fn (Builder $query) => $query->visibleToMembers()->where('scoring_type', QuestionScoring::Weighted)])
            ->latest('id')->get();
        $exams->where('random_questions', false)->load(['scoringQuestions' => fn (Builder|Relation $questions): Builder|Relation => $questions->limit(200)]);
        $banks = $exams->map(function (Exam $exam) use ($attempts, $entitlements, $user, $claims, $tokenPackages, $tokenExamIds, $learningAllowed, $hasPremium): array {
            $history = $attempts->get($exam->slug, collect());
            $covering = $entitlements->filter(fn (UserPackage $item): bool => $item->tier === PackageTier::Premium
                || (($exam->kind === 'tryout' || $item->tier === null) && $item->exams->contains('id', $exam->id)));
            $claim = $claims->get($exam->id);
            $freeAccess = $user->isAdmin() || ($exam->free_claim_enabled ? ($hasPremium || $claim?->status === 'approved') : $exam->access_level === MaterialAccess::Free);
            $hasAccess = $freeAccess || (! $exam->free_claim_enabled && $covering->isNotEmpty());
            $topicReady = $exam->topicIsReadyFor($user);
            $isAdminPreview = $user->isAdmin() && ($exam->free_claim_enabled || ($exam->requires_topic_completion
                && $exam->topic?->hasCompletedMaterials() !== true));
            $canStart = $topicReady && ($freeAccess || (! $exam->free_claim_enabled && $covering->contains(fn (UserPackage $item): bool => $exam->kind !== 'tryout' || $item->hasTryoutQuota())));
            if ($exam->kind !== 'tryout' && ! $learningAllowed) {
                $hasAccess = false;
                $canStart = false;
            }
            $remaining = $freeAccess || $covering->contains(fn (UserPackage $item): bool => $item->tryout_quota === null)
                ? null : $covering->sum(fn (UserPackage $item): int => $item->remainingTryouts());

            return ['exam' => $exam, 'open' => $history->firstWhere('status', ExamAttempt::IN_PROGRESS),
                'learningLocked' => $exam->kind !== 'tryout' && ! $learningAllowed,
                'hasAccess' => $hasAccess, 'canStart' => $canStart, 'topicReady' => $topicReady,
                'tokenPackage' => ! $canStart && $topicReady && isset($tokenExamIds[$exam->id]) ? $tokenPackages->first() : null,
                'isTokenRefill' => $hasAccess || $history->isNotEmpty(),
                'isAdminPreview' => $isAdminPreview, 'remainingTryouts' => $remaining,
                'claim' => $claim, 'last' => $history->firstWhere('status', ExamAttempt::COMPLETED)];
        });

        return ['banks' => $banks, 'kind' => $kind, 'tokenCount' => $tokenPackages->sum('tryout_tokens'),
            'learningLocked' => $kind !== 'tryout' && ! $learningAllowed,
            'completedCount' => $attempts->flatten(1)->where('status', ExamAttempt::COMPLETED)->count()];
    }

    public function start(User $user, string $kind, string $slug): ExamAttempt
    {
        $exam = Exam::query()->where('kind', $kind)->where('slug', $slug)->firstOrFail();

        return ExamAttempt::resumeOrStart($user, $exam);
    }

    public function active(User $user, string $kind, string $slug, ?int $id): ExamAttempt
    {
        abort_if($kind !== 'tryout' && ! $user->canAccessLearningFeatures(), 403, 'Latihan soal tersedia dalam paket Premium.');

        return ExamAttempt::query()->whereBelongsTo($user)->where('kind', $kind)->where('bank_slug', $slug)
            ->when($id !== null, fn (Builder $query) => $query->whereKey($id))
            ->when($id === null, fn (Builder $query) => $query->where('status', ExamAttempt::IN_PROGRESS))->latest('id')->firstOrFail();
    }

    /** @return array<string, mixed> */
    public function result(User $user, string $kind, string $slug, ?int $id): array
    {
        ExamAttempt::finalizeExpiredFor($user);
        $attempt = ExamAttempt::query()->whereBelongsTo($user)->where('kind', $kind)
            ->where('bank_slug', $slug)->completed()
            ->when($id !== null, fn (Builder $query) => $query->whereKey($id))
            ->latest('submitted_at')->latest('id')->first();
        $exam = Exam::query()->published()->where('kind', $kind)->where('slug', $slug)->first();
        abort_if(($id !== null && $attempt === null) || ($attempt === null && $exam === null), 404);
        $recent = $attempt ? ExamAttempt::query()->whereBelongsTo($user)->where('kind', $kind)
            ->where('bank_slug', $slug)->completed()->latest('submitted_at')->latest('id')
            ->limit(5)->get()->reverse()->values() : collect();
        $canViewExplanations = $user->canViewExplanations();
        $reviewQuestions = array_map(
            fn (array $question): array => $canViewExplanations
                ? $question : array_diff_key($question, array_flip(['explain', 'explainHtml'])),
            $attempt?->question_snapshot['questions'] ?? [],
        );

        return [
            'attempt' => $attempt, 'kind' => $kind, 'exam' => $exam,
            'canViewExplanations' => $canViewExplanations, 'reviewQuestions' => $reviewQuestions,
            'weightedQuestionCount' => count(array_filter($attempt?->question_snapshot['questions'] ?? [], fn (array $question): bool => ($question['scoringType'] ?? 'binary') === 'weighted')),
            'sections' => $attempt?->sectionBreakdown() ?? [],
            'sectionGrades' => $attempt && $kind === 'tryout' ? $attempt->sectionGrades() : [],
            'compositionChart' => $attempt ? ['type' => 'donut', 'size' => 190, 'segments' => [
                ['value' => $attempt->correct_count, 'color' => '#2DD4A7'],
                ['value' => $attempt->wrong_count, 'color' => '#FF7A5C'],
                ['value' => $attempt->weighted_answered_count, 'color' => '#0191ED'],
                ['value' => $attempt->blank_count, 'color' => '#B9CFE0']], 'aria' => 'Komposisi jawaban sesi ini'] : null,
            'trendChart' => $recent->isNotEmpty() ? ['type' => 'line',
                'labels' => $recent->map(fn (ExamAttempt $item, int $index): string => 'S'.($index + 1))->all(),
                'values' => $recent->pluck('score')->all(), 'avg' => (int) round($recent->avg('score')),
                'height' => 230, 'aria' => 'Perkembangan nilai sesi'] : null,
        ];
    }
}
