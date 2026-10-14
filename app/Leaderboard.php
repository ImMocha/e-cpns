<?php

declare(strict_types=1);

namespace App;

use App\Enums\Province;
use App\Enums\UserRole;
use App\Models\ExamAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class Leaderboard
{
    public function rankings(?Province $province = null): Builder
    {
        $attempts = ExamAttempt::query()->completed()->where('kind', 'tryout')
            ->where('max_score', '>', 0)
            ->select(['user_id', 'score', 'max_score'])
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY (1.0 * score / max_score) DESC, score DESC, submitted_at ASC, id ASC) AS attempt_order');

        $bestScores = DB::query()->fromSub($attempts, 'results')->where('attempt_order', 1)
            ->select(['user_id', 'score', 'max_score'])
            ->selectRaw('ROUND(100.0 * score / max_score, 2) AS score_percent');

        $rankings = User::query()->active()->role(UserRole::User)->whereNotNull('email_verified_at')
            ->joinSub($bestScores, 'best_scores', 'users.id', '=', 'best_scores.user_id')
            ->when($province !== null, fn (EloquentBuilder $query): EloquentBuilder => $query->where('users.province', $province->value))
            ->select(['users.id', 'users.name', 'users.province', 'best_scores.score', 'best_scores.max_score', 'best_scores.score_percent'])
            ->selectRaw('RANK() OVER (ORDER BY best_scores.score_percent DESC) AS position');

        return DB::query()->fromSub($rankings, 'leaderboard')->orderBy('position')->orderBy('id');
    }
}
