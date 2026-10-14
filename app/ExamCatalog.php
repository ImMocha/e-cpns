<?php

declare(strict_types=1);

namespace App;

use App\Enums\MaterialAccess;
use App\Enums\QuestionScoring;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ExamCatalog
{
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

        $banks = Exam::query()->visibleToMembers()->where('kind', $kind)
            ->when($topicIds !== null, fn (Builder $query): Builder => $query->whereIn('topic_id', $topicIds))
            ->with(['topic' => fn (Builder|Relation $topic): Builder|Relation => $topic->withLearningProgress($user)])
            ->withCount(['questions as available_count' => fn (Builder $query) => $query->visibleToMembers(),
                'questions as weighted_available_count' => fn (Builder $query) => $query->visibleToMembers()->where('scoring_type', QuestionScoring::Weighted)])
            ->latest('id')->get()->map(function (Exam $exam) use ($attempts, $entitlements, $user): array {
                $history = $attempts->get($exam->slug, collect());
                $covering = $entitlements->filter(fn (UserPackage $item): bool => $item->exams->contains('id', $exam->id));
                $freeAccess = $user->isAdmin() || $exam->access_level === MaterialAccess::Free;
                $hasAccess = $freeAccess || $covering->isNotEmpty();
                $topicReady = $exam->topicIsReadyFor($user);
                $isAdminPreview = $user->isAdmin() && $exam->requires_topic_completion
                    && $exam->topic?->hasCompletedMaterials() !== true;
                $canStart = $topicReady && ($freeAccess || $covering->contains(fn (UserPackage $item): bool => $exam->kind === 'quiz' || $item->hasTryoutQuota()));
                $remaining = $freeAccess || $covering->contains(fn (UserPackage $item): bool => $item->tryout_quota === null)
                    ? null : $covering->sum(fn (UserPackage $item): int => $item->remainingTryouts());

                return ['exam' => $exam, 'open' => $history->firstWhere('status', ExamAttempt::IN_PROGRESS),
                    'hasAccess' => $hasAccess, 'canStart' => $canStart, 'topicReady' => $topicReady,
                    'isAdminPreview' => $isAdminPreview, 'remainingTryouts' => $remaining,
                    'last' => $history->firstWhere('status', ExamAttempt::COMPLETED)];
            });

        return ['banks' => $banks, 'kind' => $kind,
            'completedCount' => $attempts->flatten(1)->where('status', ExamAttempt::COMPLETED)->count()];
    }

    public function start(User $user, string $kind, string $slug): ExamAttempt
    {
        $exam = Exam::query()->where('kind', $kind)->where('slug', $slug)->firstOrFail();

        return ExamAttempt::resumeOrStart($user, $exam);
    }

    public function active(User $user, string $kind, string $slug, ?int $id): ExamAttempt
    {
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
