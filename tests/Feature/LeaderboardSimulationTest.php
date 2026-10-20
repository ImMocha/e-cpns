<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LeaderboardAccess;
use App\Enums\Province;
use App\Models\ExamAttempt;
use App\Models\LeaderboardBot;
use App\Models\User;
use App\Models\UserPackage;
use Database\Seeders\LeaderboardBotSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use Tests\TestCase;

class LeaderboardSimulationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_activate_and_deactivate_seeded_simulations_without_changing_real_results(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create(['province' => Province::KalimantanSelatan]);
        UserPackage::factory()->for($member)->create(['leaderboard_access' => LeaderboardAccess::Local]);
        $this->completedTryout($member, 80);

        $this->actingAs($admin)->get(route('admin.leaderboard-simulation.index'))
            ->assertSee('Nonaktif')->assertSee('Aktifkan peserta virtual');
        $this->patch(route('admin.leaderboard-simulation.update'), ['enabled' => '1'])
            ->assertRedirect(route('admin.leaderboard-simulation.index'));

        $this->assertDatabaseHas('leaderboard_settings', ['id' => 1, 'simulations_enabled' => true]);
        $counts = LeaderboardBot::query()->selectRaw('province, COUNT(*) AS total')->groupBy('province')->pluck('total', 'province');
        $this->assertCount(38, $counts);
        $this->assertGreaterThanOrEqual(15, $counts->min());
        $this->assertLessThanOrEqual(25, $counts->max());
        $total = LeaderboardBot::query()->count();
        $localCount = (int) $counts[Province::KalimantanSelatan->value];
        $this->assertDatabaseCount('users', 2);
        $this->actingAs($member)->get(route('leaderboard.index'))
            ->assertSee('Peserta virtual memakai nilai contoh')->assertDontSee('· Virtual')
            ->assertViewHas('participants', fn (LengthAwarePaginator $rows): bool => $rows->total() === $localCount + 1)
            ->assertViewHas('ownRank', fn (stdClass $rank): bool => (int) $rank->score === 80);

        $this->actingAs($admin)->patch(route('admin.leaderboard-simulation.update'), ['enabled' => '0'])
            ->assertRedirect(route('admin.leaderboard-simulation.index'));

        $this->assertDatabaseHas('leaderboard_settings', ['id' => 1, 'simulations_enabled' => false]);
        $this->assertDatabaseCount('leaderboard_bots', $total);
        $this->actingAs($member)->get(route('leaderboard.index'))
            ->assertDontSee('· Virtual')
            ->assertViewHas('participants', fn (LengthAwarePaginator $rows): bool => $rows->total() === 1)
            ->assertViewHas('ownRank', fn (stdClass $rank): bool => (int) $rank->position === 1);

        $this->actingAs($admin)->patch(route('admin.leaderboard-simulation.update'), ['enabled' => '1']);
        $this->assertSame($counts->all(), LeaderboardBot::query()->selectRaw('province, COUNT(*) AS total')->groupBy('province')->pluck('total', 'province')->all());
    }

    public function test_simulations_follow_local_and_national_scope_and_cannot_be_mistaken_for_the_current_user(): void
    {
        $member = User::factory()->create(['province' => Province::KalimantanSelatan]);
        UserPackage::factory()->for($member)->create(['leaderboard_access' => LeaderboardAccess::National]);
        $this->completedTryout($member, 80);
        LeaderboardBot::factory()->create(['name' => 'Contoh Lokal', 'province' => Province::KalimantanSelatan, 'score' => 450]);
        LeaderboardBot::factory()->create(['name' => 'Contoh Nasional', 'province' => Province::JawaTimur, 'score' => 500]);
        $this->actingAs(User::factory()->admin()->create())->patch(route('admin.leaderboard-simulation.update'), ['enabled' => '1']);

        $this->actingAs($member)->get(route('leaderboard.index'))
            ->assertSee('Contoh Lokal')->assertDontSee('Contoh Nasional')
            ->assertSee('Peserta virtual memakai nilai contoh')->assertDontSee('· Virtual')
            ->assertViewHas('ownRank', fn (stdClass $rank): bool => $rank->participant_type === 'real' && (int) $rank->position > 1);
        $this->get(route('leaderboard.index', ['scope' => 'national']))
            ->assertSee('Contoh Nasional')
            ->assertViewHas('ownRank', fn (stdClass $rank): bool => $rank->participant_type === 'real' && (int) $rank->position > 2);
    }

    public function test_non_admin_cannot_change_simulation_and_invalid_value_is_rejected(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)->get(route('admin.leaderboard-simulation.index'))->assertForbidden();
        $this->patch(route('admin.leaderboard-simulation.update'), ['enabled' => '1'])->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.leaderboard-simulation.update'), ['enabled' => 'surprise'])
            ->assertSessionHasErrors('enabled');

        $this->assertDatabaseCount('leaderboard_bots', 0);
        $this->assertDatabaseCount('leaderboard_settings', 0);
    }

    public function test_member_without_package_still_cannot_see_simulated_participants(): void
    {
        $member = User::factory()->create();
        LeaderboardBot::factory()->create(['name' => 'Nama Simulasi Rahasia']);
        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.leaderboard-simulation.update'), ['enabled' => '1']);

        $this->actingAs($member)->get(route('leaderboard.index'))
            ->assertSee('Akses paket diperlukan')->assertDontSee('Nama Simulasi Rahasia')
            ->assertViewHas('participants', null);
    }

    public function test_seeder_can_be_repeated_without_overwriting_a_changed_profile_or_enabling_the_feature(): void
    {
        $this->seed(LeaderboardBotSeeder::class);
        $profile = LeaderboardBot::query()->firstOrFail();
        $profile->update(['name' => 'Contoh yang disesuaikan', 'score' => 72]);
        $ids = LeaderboardBot::query()->orderBy('id')->pluck('id')->all();

        $this->seed(LeaderboardBotSeeder::class);

        $this->assertSame($ids, LeaderboardBot::query()->orderBy('id')->pluck('id')->all());
        $this->assertDatabaseHas('leaderboard_bots', ['id' => $profile->id, 'name' => 'Contoh yang disesuaikan', 'score' => 72]);
        $this->assertDatabaseCount('leaderboard_settings', 0);
        $this->assertGreaterThanOrEqual(15, LeaderboardBot::query()->where('province', Province::KalimantanSelatan->value)->count());
    }

    public function test_admin_can_reduce_and_expand_population_without_deleting_profiles_or_including_hidden_rows(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->patch(route('admin.leaderboard-simulation.update'), [
            'enabled' => 1, 'minimum_per_province' => 25, 'maximum_per_province' => 25,
        ])->assertRedirect();
        $this->assertDatabaseCount('leaderboard_bots', 950);

        $this->patch(route('admin.leaderboard-simulation.update'), [
            'enabled' => 1, 'minimum_per_province' => 15, 'maximum_per_province' => 15,
        ])->assertRedirect();

        $this->assertDatabaseCount('leaderboard_bots', 950);
        $this->assertSame(570, LeaderboardBot::query()->where('is_visible', true)->count());
        $this->get(route('leaderboard.index', ['scope' => 'national']))
            ->assertViewHas('participants', fn (LengthAwarePaginator $rows): bool => $rows->total() === 570);

        $this->patch(route('admin.leaderboard-simulation.update'), [
            'enabled' => 1, 'minimum_per_province' => 25, 'maximum_per_province' => 25,
        ])->assertRedirect();
        $this->assertDatabaseCount('leaderboard_bots', 950);
        $this->assertSame(950, LeaderboardBot::query()->where('is_visible', true)->count());
    }

    #[DataProvider('invalidPopulationRanges')]
    public function test_invalid_population_range_does_not_change_settings(array $range, string $error): void
    {
        $this->actingAs(User::factory()->admin()->create())->patch(route('admin.leaderboard-simulation.update'), $range + ['enabled' => 1])
            ->assertSessionHasErrors($error);

        $this->assertDatabaseCount('leaderboard_settings', 0);
        $this->assertDatabaseCount('leaderboard_bots', 0);
    }

    /** @return array<string, array{array<string, mixed>, string}> */
    public static function invalidPopulationRanges(): array
    {
        return [
            'zero minimum' => [['minimum_per_province' => 0], 'minimum_per_province'],
            'maximum too large' => [['maximum_per_province' => 101], 'maximum_per_province'],
            'fraction' => [['minimum_per_province' => 15.5], 'minimum_per_province'],
            'inverted' => [['minimum_per_province' => 25, 'maximum_per_province' => 15], 'maximum_per_province'],
        ];
    }

    public function test_real_and_virtual_scores_are_displayed_and_ranked_as_raw_points(): void
    {
        $member = User::factory()->create(['province' => Province::KalimantanSelatan]);
        $peer = User::factory()->create(['province' => Province::KalimantanSelatan]);
        UserPackage::factory()->for($member)->create(['leaderboard_access' => LeaderboardAccess::Local]);
        ExamAttempt::factory()->tryout()->for($member)->create([
            'status' => ExamAttempt::COMPLETED, 'score' => 10, 'max_score' => 15, 'submitted_at' => now(),
        ]);
        ExamAttempt::factory()->tryout()->for($peer)->create([
            'status' => ExamAttempt::COMPLETED, 'score' => 400, 'max_score' => 500, 'submitted_at' => now(),
        ]);
        LeaderboardBot::factory()->create(['province' => Province::KalimantanSelatan, 'score' => 62.37]);
        $this->actingAs(User::factory()->admin()->create())->patch(route('admin.leaderboard-simulation.update'), [
            'enabled' => 1, 'minimum_per_province' => 1, 'maximum_per_province' => 1,
        ])->assertRedirect();

        $this->actingAs($member)->get(route('leaderboard.index'))
            ->assertSee('Skor (poin)')->assertSee('10 / 15')->assertSee('400 / 500')->assertSee('62,37 poin')
            ->assertSee('Dari 15 poin maksimal.')->assertDontSee(' / 100')->assertDontSee('skala 0–100')
            ->assertViewHas('ownRank', fn (stdClass $rank): bool => (int) $rank->position === 3);
    }

    public function test_seeded_national_scores_are_varied_and_stable_across_pages_and_reseeding(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->patch(route('admin.leaderboard-simulation.update'), [
            'enabled' => 1, 'minimum_per_province' => 25, 'maximum_per_province' => 25,
        ])->assertRedirect();
        $profiles = LeaderboardBot::query()->orderBy('id')->get();
        $snapshot = $profiles->toArray();

        $this->assertCount(950, $profiles);
        $this->assertCount(950, $profiles->pluck('score')->unique());
        $this->assertCount(950, $profiles->pluck('name')->unique());
        $this->assertGreaterThanOrEqual(42, (float) $profiles->min('score'));
        $this->assertLessThanOrEqual(97, (float) $profiles->max('score'));
        $this->get(route('leaderboard.index', ['scope' => 'national']))
            ->assertViewHas('participants', fn (LengthAwarePaginator $rows): bool => $rows->getCollection()->map(fn (stdClass $row): int => (int) $row->position)->all() === range(1, 25));
        $this->get(route('leaderboard.index', ['scope' => 'national', 'page' => 2]))
            ->assertViewHas('participants', fn (LengthAwarePaginator $rows): bool => $rows->getCollection()->map(fn (stdClass $row): int => (int) $row->position)->all() === range(26, 50));
        $this->get(route('leaderboard.index', ['scope' => 'local']))
            ->assertViewHas('participants', fn (LengthAwarePaginator $rows): bool => $rows->getCollection()->map(fn (stdClass $row): int => (int) $row->position)->all() === range(1, 25));

        $this->seed(LeaderboardBotSeeder::class);

        $this->assertSame($snapshot, LeaderboardBot::query()->orderBy('id')->get()->toArray());
    }

    public function test_legacy_seeded_profiles_are_refreshed_once_without_changing_manual_profiles_or_real_results(): void
    {
        $legacyProfiles = LeaderboardBot::factory()->count(3)->sequence(
            ['source_key' => 'starter-Aceh-1', 'is_visible' => true],
            ['source_key' => 'starter-Aceh-2', 'is_visible' => true],
            ['source_key' => 'starter-Aceh-3', 'is_visible' => false],
        )->create(['province' => Province::Aceh, 'name' => 'Nama Contoh Lama', 'score' => 93, 'generation_version' => 1]);
        $manualProfile = LeaderboardBot::factory()->create(['name' => 'Profil Pilihan Admin', 'score' => 88.25]);
        $member = User::factory()->create();
        $this->completedTryout($member, 80);

        $this->actingAs(User::factory()->admin()->create())->patch(route('admin.leaderboard-simulation.update'), [
            'enabled' => 0, 'minimum_per_province' => 2, 'maximum_per_province' => 2,
        ])->assertRedirect();

        $updatedProfiles = LeaderboardBot::query()->whereKey($legacyProfiles->modelKeys())->orderBy('id')->get();
        $this->assertSame([2, 2, 2], $updatedProfiles->pluck('generation_version')->all());
        $this->assertSame([true, true, false], $updatedProfiles->pluck('is_visible')->all());
        $this->assertCount(3, $updatedProfiles->pluck('score')->unique());
        $this->assertNotContains('93.00', $updatedProfiles->pluck('score')->all());
        $this->assertNotContains('Nama Contoh Lama', $updatedProfiles->pluck('name')->all());
        $this->assertDatabaseHas('leaderboard_bots', ['id' => $manualProfile->id, 'name' => 'Profil Pilihan Admin', 'score' => 88.25]);
        $this->assertDatabaseHas('leaderboard_settings', ['id' => 1, 'simulations_enabled' => false]);
        $this->assertDatabaseHas('exam_attempts', ['user_id' => $member->id, 'score' => 80, 'max_score' => 100]);
        $snapshot = $updatedProfiles->toArray();

        $this->seed(LeaderboardBotSeeder::class);

        $this->assertSame($snapshot, LeaderboardBot::query()->whereKey($legacyProfiles->modelKeys())->orderBy('id')->get()->toArray());
    }

    private function completedTryout(User $user, int $score): void
    {
        ExamAttempt::factory()->tryout()->for($user)->create([
            'status' => ExamAttempt::COMPLETED,
            'score' => $score,
            'max_score' => 100,
            'submitted_at' => now(),
        ]);
    }
}
