<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LeaderboardAccess;
use App\Enums\Province;
use App\Enums\UserRole;
use App\Models\ExamAttempt;
use App\Models\Material;
use App\Models\Package;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use Tests\TestCase;

class LeaderboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_stores_province_and_explains_its_leaderboard_use(): void
    {
        Notification::fake([VerifyEmail::class]);
        $this->get(route('register'))->assertSee('Provinsi tempat tinggal')->assertSee('Provinsi hanya digunakan untuk leaderboard');

        $this->post(route('register.store'), $this->registrationPayload() + ['role' => 'admin', 'leaderboard_access' => 'national'])
            ->assertRedirect(route('verification.notice'))->assertSessionHasNoErrors();

        $user = User::sole();
        $this->assertSame(Province::KalimantanSelatan, $user->province);
        $this->assertSame(UserRole::User, $user->role);
        $this->assertSame(LeaderboardAccess::None, $user->leaderboardAccess());
        $this->assertAuthenticatedAs($user);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    #[DataProvider('invalidProvinces')]
    public function test_registration_rejects_missing_or_unknown_province(mixed $province): void
    {
        $this->post(route('register.store'), array_replace($this->registrationPayload(), ['province' => $province]))
            ->assertSessionHasErrors('province');

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    /** @return array<string, array{mixed}> */
    public static function invalidProvinces(): array
    {
        return ['missing' => [null], 'unknown' => ['Provinsi Tidak Ada'], 'array' => [['Kalimantan Selatan']]];
    }

    public function test_existing_user_can_fill_province_from_profile_to_open_local_ranking(): void
    {
        $user = User::factory()->create(['province' => null]);
        $this->grant($user, LeaderboardAccess::Local);
        $this->actingAs($user)->get(route('leaderboard.index'))->assertSee('Lengkapi provinsi tempat tinggalmu')
            ->assertViewHas('participants', null);

        $this->patch(route('profile.update'), ['name' => $user->name, 'province' => Province::KalimantanSelatan->value])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'province' => 'Kalimantan Selatan']);
        $this->get(route('leaderboard.index'))->assertSee('Leaderboard Kalimantan Selatan')
            ->assertViewHas('participants', fn (LengthAwarePaginator $participants): bool => $participants->total() === 0);
    }

    public function test_profile_does_not_accept_an_unknown_province(): void
    {
        $user = User::factory()->create(['province' => Province::KalimantanSelatan]);

        $this->actingAs($user)->patch(route('profile.update'), ['name' => $user->name, 'province' => 'Bukan Provinsi'])
            ->assertSessionHasErrors('province');

        $this->assertSame(Province::KalimantanSelatan, $user->fresh()->province);
    }

    public function test_user_without_leaderboard_package_sees_lock_without_participant_data(): void
    {
        $user = User::factory()->create();
        $peer = User::factory()->create(['name' => 'Peserta yang tidak boleh bocor']);
        $this->createCompletedTryout($peer, 450);

        $this->actingAs($user)->get(route('leaderboard.index', ['scope' => 'national']))->assertOk()
            ->assertSee('Buka leaderboard lokal atau nasional')->assertDontSee($peer->name)
            ->assertViewHas('participants', null)->assertViewHas('ownRank', null);
    }

    public function test_local_package_cannot_open_national_ranking_by_url(): void
    {
        $user = User::factory()->create();
        $this->grant($user, LeaderboardAccess::Local);

        $this->actingAs($user)->get(route('leaderboard.index', ['scope' => 'national']))->assertForbidden();
    }

    public function test_local_ranking_uses_own_province_and_only_the_best_completed_tryout(): void
    {
        $user = User::factory()->create(['province' => Province::KalimantanSelatan]);
        $peer = User::factory()->create(['name' => 'Peserta lokal', 'province' => Province::KalimantanSelatan]);
        $outsider = User::factory()->create(['name' => 'Peserta provinsi lain', 'province' => Province::JawaTimur]);
        $this->grant($user, LeaderboardAccess::Local);
        $this->createCompletedTryout($user, 400);
        $this->createCompletedTryout($user, 100, 100);
        $this->createCompletedTryout($user, 450);
        $this->createCompletedTryout($user, 500, 500, ['status' => ExamAttempt::IN_PROGRESS]);
        $this->createCompletedTryout($user, 100, 100, ['kind' => 'quiz']);
        $this->createCompletedTryout($peer, 425);
        $this->createCompletedTryout($outsider, 500);

        $response = $this->actingAs($user)->get(route('leaderboard.index', ['province' => 'Jawa Timur']));

        $response->assertSee('Leaderboard Kalimantan Selatan')->assertSee('Peserta lokal')->assertDontSee($outsider->name)
            ->assertViewHas('participants', fn (LengthAwarePaginator $participants): bool => $participants->total() === 2)
            ->assertViewHas('ownRank', fn (stdClass $rank): bool => (int) $rank->position === 1 && (int) $rank->score === 450);
    }

    public function test_national_package_includes_local_access_and_changes_personal_rank_by_scope(): void
    {
        $user = User::factory()->create(['province' => Province::KalimantanSelatan]);
        $local = User::factory()->create(['province' => Province::KalimantanSelatan]);
        $outsider = User::factory()->create(['name' => 'Peserta nasional', 'province' => Province::JawaTimur]);
        $this->grant($user, LeaderboardAccess::National);
        $this->createCompletedTryout($user, 300);
        $this->createCompletedTryout($local, 400);
        $this->createCompletedTryout($outsider, 450);

        $this->actingAs($user)->get(route('leaderboard.index'))->assertViewHas('ownRank', fn (stdClass $rank): bool => (int) $rank->position === 2);
        $this->get(route('leaderboard.index', ['scope' => 'national']))->assertSee('Leaderboard Nasional')
            ->assertSee($outsider->name)->assertDontSee($outsider->email)
            ->assertViewHas('ownRank', fn (stdClass $rank): bool => (int) $rank->position === 3)
            ->assertViewHas('participants', fn (LengthAwarePaginator $participants): bool => $participants->total() === 3);
    }

    public function test_equal_points_share_rank_despite_different_maximums_and_skip_following_positions(): void
    {
        $user = User::factory()->create();
        $this->grant($user, LeaderboardAccess::National);
        $this->createCompletedTryout($user, 400);
        $this->createCompletedTryout(User::factory()->create(), 400, 400);
        $this->createCompletedTryout(User::factory()->create(), 300);

        $response = $this->actingAs($user)->get(route('leaderboard.index', ['scope' => 'national']));

        $this->assertSame([1, 1, 3], $response['participants']->getCollection()->map(fn (stdClass $row): int => (int) $row->position)->all());
    }

    public function test_participant_name_is_rendered_as_text_instead_of_html(): void
    {
        $user = User::factory()->create();
        $this->grant($user, LeaderboardAccess::National);
        $peer = User::factory()->create(['name' => '<script>alert("participant")</script>']);
        $this->createCompletedTryout($peer, 400);

        $this->actingAs($user)->get(route('leaderboard.index', ['scope' => 'national']))
            ->assertSee($peer->name)->assertDontSee($peer->name, false);
    }

    public function test_personal_rank_is_available_even_outside_first_page(): void
    {
        $user = User::factory()->create();
        $this->grant($user, LeaderboardAccess::National);
        $this->createCompletedTryout($user, 200);
        foreach (User::factory()->count(26)->create() as $peer) {
            $this->createCompletedTryout($peer, 450);
        }

        $this->actingAs($user)->get(route('leaderboard.index', ['scope' => 'national']))
            ->assertViewHas('ownRank', fn (stdClass $rank): bool => (int) $rank->position === 27)
            ->assertViewHas('participants', fn (LengthAwarePaginator $participants): bool => $participants->count() === 25 && $participants->total() === 27);
        $this->get(route('leaderboard.index', ['scope' => 'national', 'page' => 2]))->assertSee('Kamu')
            ->assertViewHas('participants', fn (LengthAwarePaginator $participants): bool => $participants->count() === 2);
    }

    public function test_ranking_excludes_inactive_unverified_deleted_and_admin_accounts_and_zero_maximum(): void
    {
        $user = User::factory()->create();
        $this->grant($user, LeaderboardAccess::National);
        $this->createCompletedTryout($user, 300);
        $this->createCompletedTryout(User::factory()->inactive()->create(), 500);
        $this->createCompletedTryout(User::factory()->unverified()->create(), 500);
        $this->createCompletedTryout(User::factory()->admin()->create(), 500);
        $deleted = User::factory()->create();
        $this->createCompletedTryout($deleted, 500);
        $deleted->delete();
        $this->createCompletedTryout(User::factory()->create(), 0, 0);

        $this->actingAs($user)->get(route('leaderboard.index', ['scope' => 'national']))
            ->assertViewHas('participants', fn (LengthAwarePaginator $participants): bool => $participants->total() === 1)
            ->assertViewHas('ownRank', fn (stdClass $rank): bool => (int) $rank->position === 1);
    }

    /** @param array<string, mixed> $overrides */
    #[DataProvider('inactivePackages')]
    public function test_expired_revoked_or_future_package_cannot_view_ranking(array $overrides): void
    {
        $this->travelTo('2026-10-14 12:00:00');
        $user = User::factory()->create();
        UserPackage::factory()->for($user)->create($overrides + ['leaderboard_access' => LeaderboardAccess::National]);

        $this->actingAs($user)->get(route('leaderboard.index'))->assertSee('Akses paket diperlukan')
            ->assertViewHas('participants', null);
    }

    /** @return array<string, array{array<string, mixed>}> */
    public static function inactivePackages(): array
    {
        return [
            'expired' => [['expires_at' => '2026-10-14 12:00:00']],
            'revoked' => [['revoked_at' => '2026-10-14 12:00:00']],
            'future' => [['starts_at' => '2026-10-14 12:00:01']],
        ];
    }

    public function test_highest_access_from_multiple_active_packages_is_used(): void
    {
        $user = User::factory()->create();
        $this->grant($user, LeaderboardAccess::Local);
        $this->grant($user, LeaderboardAccess::National);

        $this->assertSame(LeaderboardAccess::National, $user->leaderboardAccess());
    }

    public function test_admin_can_configure_leaderboard_access_and_manual_grant_preserves_it(): void
    {
        $admin = User::factory()->admin()->create();
        $material = Material::factory()->premium()->video()->create();
        $payload = ['name' => 'Paket Lokal', 'price' => 75000, 'duration_days' => 30, 'tryout_quota' => 2,
            'position' => 0, 'is_active' => true, 'material_ids' => [$material->id], 'exam_ids' => [],
            'leaderboard_access' => 'local'];
        $this->actingAs($admin)->post(route('admin.packages.store'), $payload)->assertSessionHasNoErrors();
        $package = Package::sole();
        $user = User::factory()->create();
        $grant = UserPackage::grant($user, $package, $admin);

        $this->put(route('admin.packages.update', $package), array_replace($payload, ['leaderboard_access' => 'national']))
            ->assertSessionHasNoErrors();

        $this->assertSame(LeaderboardAccess::National, $package->fresh()->leaderboard_access);
        $this->assertSame(LeaderboardAccess::Local, $grant->fresh()->leaderboard_access);
        $this->assertSame(LeaderboardAccess::Local, $user->leaderboardAccess());
        $this->actingAs($user)->get(route('packages.index'))->assertSee('Leaderboard lokal + nasional')->assertSee('Leaderboard lokal');
    }

    public function test_package_rejects_unknown_leaderboard_access(): void
    {
        $this->actingAs(User::factory()->admin()->create())->post(route('admin.packages.store'), [
            'name' => 'Paket Invalid', 'price' => 75000, 'position' => 0, 'is_active' => false,
            'leaderboard_access' => 'worldwide',
        ])->assertSessionHasErrors('leaderboard_access');

        $this->assertDatabaseCount('packages', 0);
    }

    /** @return array<string, mixed> */
    private function registrationPayload(): array
    {
        return ['name' => 'Peserta Baru', 'email' => 'pesertabaru@example.test',
            'password' => 'password123', 'password_confirmation' => 'password123', 'terms' => 1,
            'province' => Province::KalimantanSelatan->value];
    }

    private function grant(User $user, LeaderboardAccess $access): UserPackage
    {
        return UserPackage::factory()->for($user)->create(['leaderboard_access' => $access]);
    }

    /** @param array<string, mixed> $overrides */
    private function createCompletedTryout(User $user, int $score, int $maximum = 500, array $overrides = []): ExamAttempt
    {
        return ExamAttempt::factory()->tryout()->for($user)->create(array_replace([
            'status' => ExamAttempt::COMPLETED, 'score' => $score, 'max_score' => $maximum,
            'submitted_at' => now(),
        ], $overrides));
    }
}
