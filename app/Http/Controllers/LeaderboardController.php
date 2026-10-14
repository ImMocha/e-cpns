<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\LeaderboardAccess;
use App\Leaderboard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function __invoke(Request $request, Leaderboard $leaderboard): View
    {
        $filters = $request->validate([
            'scope' => ['nullable', 'in:local,national'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $user = $request->user();
        $access = $user->leaderboardAccess();
        $scope = $filters['scope'] ?? 'local';
        $hasAccess = $access !== LeaderboardAccess::None;

        if ($hasAccess && $scope === 'national') {
            abort_unless($access === LeaderboardAccess::National, 403, 'Paketmu belum mencakup leaderboard nasional.');
        }

        $needsProvince = $scope === 'local' && $user->province === null;
        $participants = null;
        $ownRank = null;
        if ($hasAccess && ! $needsProvince) {
            $rankings = $leaderboard->rankings($scope === 'local' ? $user->province : null);
            $ownRank = (clone $rankings)->where('id', $user->id)->first();
            $participants = $rankings->paginate(25)->withQueryString();
        }

        return view('Dashboard.User.leaderboard.index', [
            'access' => $access, 'scope' => $scope, 'hasAccess' => $hasAccess,
            'needsProvince' => $needsProvince, 'province' => $user->province,
            'participants' => $participants, 'ownRank' => $ownRank,
        ]);
    }
}
