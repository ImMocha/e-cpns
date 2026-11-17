<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\LeaderboardAccess;
use App\Leaderboard;
use App\LeaderboardSimulation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function __invoke(Request $request, Leaderboard $leaderboard, LeaderboardSimulation $simulation): View
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
        $totalParticipants = 0;
        $participants = null;
        $ownRank = null;
        $simulatedCount = 0;
        $referenceTryout = null;
        $maximumScore = null;
        if ($hasAccess && ! $needsProvince) {
            $referenceTryout = $leaderboard->referenceTryout();
            $maximumScore = $referenceTryout?->maximumScore();
            $rankings = $leaderboard->rankings($scope === 'local' ? $user->province : null, $referenceTryout);
            $ownRank = (clone $rankings)->where('id', $user->id)->where('participant_type', 'real')->first();
            $perPage = 25;
            $rankPage = $ownRank && $request->boolean('mine')
                ? intdiv((int) $ownRank->position - 1, $perPage) + 1
                : null;
            $participants = $rankings->paginate($perPage, page: $rankPage)->appends(['scope' => $scope]);
            $totalParticipants = $participants->total();

            if ($simulation->enabled()) {
                $simulatedCount = $simulation->bots($scope === 'local' ? $user->province : null)->count();
            }
        }

        return view('Dashboard.User.leaderboard.index', [
            'access' => $access, 'scope' => $scope, 'hasAccess' => $hasAccess,
            'needsProvince' => $needsProvince, 'province' => $user->province,
            'totalParticipants' => $totalParticipants,
            'participants' => $participants, 'ownRank' => $ownRank,
            'simulatedCount' => $simulatedCount,
            'referenceTryout' => $referenceTryout, 'maximumScore' => $maximumScore,
        ]);
    }
}
