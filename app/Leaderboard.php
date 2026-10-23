<?php

declare(strict_types=1);

namespace App;

use App\Enums\Province;
use App\Enums\QuestionScoring;
use App\Enums\UserRole;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\LeaderboardBot;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class Leaderboard
{
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

        $attempts->select(['user_id', 'score', 'max_score'])
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY score DESC, submitted_at ASC, id ASC) AS attempt_order');

        $bestScores = DB::query()->fromSub($attempts, 'results')->where('attempt_order', 1)
            ->select(['user_id', 'score', 'max_score']);

        $realParticipants = User::query()->active()->role(UserRole::User)->whereNotNull('email_verified_at')
            ->joinSub($completedParticipants, 'completed_tryouts', 'users.id', '=', 'completed_tryouts.user_id')
            ->leftJoinSub($bestScores, 'best_scores', 'users.id', '=', 'best_scores.user_id')
            ->when($province !== null, fn (EloquentBuilder $query): EloquentBuilder => $query->where('users.province', $province->value))
            ->select(['users.id', 'users.name', 'users.province'])
            ->selectRaw('COALESCE(best_scores.score, 0) AS score, COALESCE(best_scores.max_score, ?) AS max_score', [$maximumScore ?? 0])
            ->selectRaw('? AS participant_type', ['real']);

        $participants = $realParticipants->toBase();

        if ($this->simulation->enabled()) {
            $simulatedParticipants = LeaderboardBot::query()->where('is_visible', true)
                ->when($province !== null, fn (EloquentBuilder $query): EloquentBuilder => $query->where('province', $province->value))
                ->select(['id', 'name', 'province']);

            if ($maximumScore !== null) {
                $simulatedParticipants->selectRaw('ROUND(score * ? / 550) AS score, ? AS max_score, ? AS participant_type', [$maximumScore, $maximumScore, 'simulation']);
            } else {
                $simulatedParticipants->addSelect('score')->selectRaw('NULL AS max_score, ? AS participant_type', ['simulation']);
            }

            $participants->unionAll($simulatedParticipants->toBase());
        }

        $rankings = DB::query()->fromSub($participants, 'participants')
            ->select('participants.*')
            ->selectRaw('RANK() OVER (ORDER BY participants.score DESC) AS position');

        return DB::query()->fromSub($rankings, 'leaderboard')
            ->orderBy('position')->orderBy('participant_type')->orderBy('id');
    }
}
