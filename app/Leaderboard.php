<?php

declare(strict_types=1);

namespace App;

use App\Enums\Province;
use App\Enums\QuestionScoring;
use App\Enums\UserRole;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class Leaderboard
{
    private const string SCORE_ORDER = 'score DESC, COALESCE(tkp_score, -1) DESC, COALESCE(tiu_score, -1) DESC, COALESCE(twk_score, -1) DESC';

    public function __construct(private readonly LeaderboardSimulation $simulation) {}

    public function referenceTryout(): ?Exam
    {
        return Exam::query()->visibleToMembers()->where('kind', 'tryout')
            ->with('scoringQuestions')
            ->withCount([
                'questions as available_count' => fn (EloquentBuilder $query): EloquentBuilder => $query->visibleToMembers(),
                'questions as weighted_available_count' => fn (EloquentBuilder $query): EloquentBuilder => $query->visibleToMembers()->where('scoring_type', QuestionScoring::Weighted),
            ])
            ->latest('id')->get()
            ->filter(fn (Exam $exam): bool => $exam->maximumScore() > 0)
            ->sortByDesc(fn (Exam $exam): int => $exam->maximumScore())->first();
    }

    public function rankings(?Province $province = null, ?Exam $referenceTryout = null): Builder
    {
        $referenceTryout ??= $this->referenceTryout();
        $maximumScore = $referenceTryout?->maximumScore();
        $attempts = ExamAttempt::query()->completed()->where('kind', 'tryout')
            ->where('max_score', '>', 0);
        $completedParticipants = (clone $attempts)->select('user_id')->distinct();

        if ($maximumScore !== null) {
            $attempts->where('max_score', $maximumScore)
                ->whereIn('bank_slug', Exam::query()->visibleToMembers()->where('kind', 'tryout')->select('slug'));
        }

        $attempts->select(['id', 'user_id', 'submitted_at', 'score', 'max_score']);
        $grammar = $attempts->getQuery()->getGrammar();
        foreach (['TKP' => 'tkp_score', 'TIU' => 'tiu_score', 'TWK' => 'twk_score'] as $section => $column) {
            $selector = $grammar->wrap("section_scores->{$section}->score");
            $attempts->selectRaw("CAST(NULLIF({$selector}, 'null') AS DECIMAL(10, 0)) AS {$column}");
        }

        $orderedAttempts = DB::query()->fromSub($attempts, 'eligible_attempts')
            ->select('*')
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY '.self::SCORE_ORDER.', submitted_at ASC, id ASC) AS attempt_order');

        $bestScores = DB::query()->fromSub($orderedAttempts, 'results')->where('attempt_order', 1)
            ->select(['user_id', 'score', 'max_score', 'tkp_score', 'tiu_score', 'twk_score']);

        $realParticipants = User::query()->active()->role(UserRole::User)->whereNotNull('email_verified_at')
            ->joinSub($completedParticipants, 'completed_tryouts', 'users.id', '=', 'completed_tryouts.user_id')
            ->leftJoinSub($bestScores, 'best_scores', 'users.id', '=', 'best_scores.user_id')
            ->when($province !== null, fn (EloquentBuilder $query): EloquentBuilder => $query->where('users.province', $province->value))
            ->select(['users.id', 'users.name', 'users.province'])
            ->selectRaw('COALESCE(best_scores.score, 0) AS score, COALESCE(best_scores.max_score, ?) AS max_score', [$maximumScore ?? 0])
            ->addSelect(['best_scores.tkp_score', 'best_scores.tiu_score', 'best_scores.twk_score'])
            ->selectRaw('? AS participant_type', ['real']);

        $participants = $realParticipants->toBase();

        if ($this->simulation->enabled()) {
            $participants->unionAll($this->simulation->scoresForLeaderboard($province, $maximumScore));
        }

        $rankings = DB::query()->fromSub($participants, 'participants')
            ->select('participants.*')
            ->selectRaw('ROW_NUMBER() OVER (ORDER BY '.self::SCORE_ORDER.', LOWER(name) ASC, participant_type ASC, id ASC) AS position');

        return DB::query()->fromSub($rankings, 'leaderboard')->orderBy('position');
    }
}
