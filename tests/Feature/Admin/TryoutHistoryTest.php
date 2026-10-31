<?php

namespace Tests\Feature\Admin;

use App\Models\ExamAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TryoutHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_tryouts_from_multiple_participants_with_scores_and_navigation(): void
    {
        $admin = User::factory()->admin()->create();
        $participant = User::factory()->create();
        ExamAttempt::factory()->tryout()->for($participant)->create([
            'title' => 'Simulasi Nasional', 'status' => ExamAttempt::COMPLETED,
            'submitted_at' => now(), 'score' => 365, 'max_score' => 550,
            'section_scores' => ['TWK' => ['score' => 85], 'TIU' => ['score' => 110], 'TKP' => ['score' => 170]],
            'duration_seconds' => 125,
        ]);
        $ongoing = ExamAttempt::factory()->tryout()->create(['title' => 'Simulasi Kedua']);
        ExamAttempt::factory()->create(['title' => 'Latihan bukan tryout']);

        $this->actingAs($admin)->get(route('admin.users.tryout-history'))
            ->assertOk()->assertSee([$participant->name, $ongoing->user->name, 'Simulasi Nasional', '365 / 550', '85 / 110 / 170', '2 mnt 5 dtk', 'Sedang dikerjakan'])
            ->assertDontSee('Latihan bukan tryout');
        $this->get(route('admin.users.index'))
            ->assertSee(route('admin.users.tryout-history'), false)
            ->assertSee(route('admin.users.tryout-history', ['user_id' => $participant->id]), false);
    }

    public function test_participant_filter_excludes_other_participants(): void
    {
        $attempt = ExamAttempt::factory()->tryout()->create(['title' => 'Sesi peserta terpilih']);
        ExamAttempt::factory()->tryout()->create(['title' => 'Sesi peserta lain']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.users.tryout-history', ['user_id' => $attempt->user_id]))
            ->assertSee('Sesi peserta terpilih')->assertDontSee('Sesi peserta lain');
    }

    public function test_search_matches_name_email_and_tryout_title_and_combines_with_status(): void
    {
        $participant = User::factory()->create(['name' => 'Nadia Pertiwi', 'email' => 'nadia@contoh.test']);
        ExamAttempt::factory()->tryout()->for($participant)->create(['title' => 'Simulasi Khusus']);
        ExamAttempt::factory()->tryout()->create(['title' => 'Sesi lain']);
        $this->actingAs(User::factory()->admin()->create());

        foreach (['Nadia', 'nadia@contoh.test', 'Simulasi Khusus'] as $search) {
            $this->get(route('admin.users.tryout-history', ['q' => $search, 'status' => 'in_progress']))
                ->assertSee('Simulasi Khusus')->assertDontSee('Sesi lain');
        }
        $this->get(route('admin.users.tryout-history', ['q' => 'Nadia', 'status' => 'completed']))
            ->assertSee('Belum ada riwayat tryout')->assertDontSee('Simulasi Khusus');
    }

    public function test_deleted_participant_history_remains_visible_and_searchable(): void
    {
        $participant = User::factory()->create(['name' => 'Peserta Lama']);
        ExamAttempt::factory()->tryout()->for($participant)->create(['title' => 'Tryout Arsip']);
        $participant->delete();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.users.tryout-history', ['q' => 'Peserta Lama', 'user_id' => $participant->id]))
            ->assertSee(['Peserta Lama', 'Tryout Arsip', 'Akun dihapus']);
    }

    public function test_history_is_paginated_and_keeps_filters(): void
    {
        $participant = User::factory()->create();
        ExamAttempt::factory()->tryout()->for($participant)->count(16)->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.users.tryout-history', ['user_id' => $participant->id, 'status' => 'in_progress']))
            ->assertViewHas('attempts', fn ($attempts) => $attempts->count() === 15 && $attempts->total() === 16)
            ->assertSee('user_id='.$participant->id, false)->assertSee('page=2', false);
    }

    public function test_expired_attempt_is_labeled_without_changing_results(): void
    {
        $attempt = ExamAttempt::factory()->tryout()->create(['expires_at' => now()->subMinute()]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.users.tryout-history'))->assertSee('Waktu habis');

        $this->assertDatabaseHas('exam_attempts', ['id' => $attempt->id, 'status' => ExamAttempt::IN_PROGRESS]);
    }

    public function test_guests_and_participants_cannot_access_admin_history(): void
    {
        $this->get(route('admin.users.tryout-history'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('admin.users.tryout-history'))->assertForbidden();
    }

    public function test_participant_and_tryout_names_are_escaped(): void
    {
        $participant = User::factory()->create(['name' => '<script>alert("participant")</script>']);
        ExamAttempt::factory()->tryout()->for($participant)->create(['title' => '<script>alert("tryout")</script>']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.users.tryout-history', ['user_id' => $participant->id]))
            ->assertSee($participant->name)
            ->assertDontSee('<script>alert("participant")</script>', false)
            ->assertDontSee('<script>alert("tryout")</script>', false);
    }

    public function test_missing_participant_returns_not_found(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.users.tryout-history', ['user_id' => 99999]))->assertNotFound();
    }

    public static function invalidFilters(): array
    {
        return ['search array' => ['q', ['bad']], 'search too long' => ['q', str_repeat('a', 201)],
            'invalid id' => ['user_id', 'abc'], 'negative id' => ['user_id', -1], 'invalid status' => ['status', 'unknown']];
    }

    #[DataProvider('invalidFilters')]
    public function test_invalid_filters_are_rejected(string $key, mixed $value): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.users.tryout-history', [$key => $value]))->assertSessionHasErrors($key);
    }
}
