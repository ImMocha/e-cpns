<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LeaderboardAccess;
use App\Enums\Province;
use App\LeaderboardSimulation;
use App\Models\ExamAttempt;
use App\Models\LeaderboardBot;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LeaderboardLimitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_both_scopes_paginate_all_entries_and_jump_to_the_members_rank(): void
    {
        $member = User::factory()->create(['province' => Province::Aceh]);
        UserPackage::factory()->for($member)->create(['leaderboard_access' => LeaderboardAccess::National]);
        ExamAttempt::factory()->tryout()->for($member)->create([
            'status' => ExamAttempt::COMPLETED, 'score' => 1, 'max_score' => 550, 'submitted_at' => now(),
        ]);
        DB::table('leaderboard_settings')->insert(['id' => 1, 'simulations_enabled' => true]);
        $rows = [];
        for ($index = 0; $index < 9230; $index++) {
            $rows[] = ['source_key' => 'entry-'.$index, 'name' => 'Peserta '.$index, 'province' => Province::Aceh->value, 'score' => 500];
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('leaderboard_bots')->insert($chunk);
        }
        foreach (['national', 'local'] as $scope) {
            $rankUrl = route('leaderboard.index', ['scope' => $scope, 'mine' => 1]);

            $this->actingAs($member)->get(route('leaderboard.index', ['scope' => $scope]))
                ->assertOk()->assertViewHas('totalParticipants', 9231)
                ->assertViewHas('participants', fn ($rows): bool => $rows->count() === 25 && $rows->lastPage() === 370)
                ->assertSee('#9231')
                ->assertSee($rankUrl.'#peringkat-saya')
                ->assertDontSee('id="peringkat-saya"', false);

            $this->get($rankUrl)
                ->assertOk()->assertViewHas('participants', fn ($rows): bool => $rows->currentPage() === 370
                    && $rows->count() === 6 && (int) $rows->last()->id === $member->id
                    && ! str_contains($rows->previousPageUrl(), 'mine='))
                ->assertSee('id="peringkat-saya"', false);
        }
    }

    public function test_admin_can_set_independent_counts_reduce_them_and_disable_bots(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->patch(route('admin.leaderboard-simulation.update'), [
            'enabled' => 1, 'national_bot_count' => 80, 'local_bot_count' => 3,
        ])->assertRedirect();
        $simulation = app(LeaderboardSimulation::class);
        $this->assertSame(80, $simulation->bots()->count());
        $this->assertSame(3, $simulation->bots(Province::Aceh)->count());
        $this->assertDatabaseHas('leaderboard_settings', ['national_bot_count' => 80, 'local_bot_count' => 3]);
        $count = LeaderboardBot::query()->count();
        $this->patch(route('admin.leaderboard-simulation.update'), [
            'enabled' => 0, 'national_bot_count' => 0, 'local_bot_count' => 0,
        ])->assertRedirect();
        $this->assertSame(0, $simulation->bots()->count());
        $this->assertSame(0, $simulation->bots(Province::Aceh)->count());
        $this->assertFalse($simulation->enabled());
        $this->assertDatabaseCount('leaderboard_bots', $count);
        $this->patch(route('admin.leaderboard-simulation.update'), [
            'enabled' => 1, 'national_bot_count' => 5001, 'local_bot_count' => -1,
        ])->assertSessionHasErrors(['national_bot_count', 'local_bot_count']);
        $this->assertDatabaseHas('leaderboard_settings', ['national_bot_count' => 0, 'local_bot_count' => 0]);
    }
}
