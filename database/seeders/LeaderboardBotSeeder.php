<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\LeaderboardSimulation;
use Illuminate\Database\Seeder;

class LeaderboardBotSeeder extends Seeder
{
    public function run(): void
    {
        app(LeaderboardSimulation::class)->seedMissing();
    }
}
