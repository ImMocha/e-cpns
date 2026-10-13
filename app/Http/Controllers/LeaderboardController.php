<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class LeaderboardController extends SliceController
{
    public function __invoke(): View
    {
        return $this->slice('Dashboard.User.leaderboard.index');
    }
}
