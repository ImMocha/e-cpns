<?php

namespace Tests\Feature\Member;

use App\Enums\ExamBank;
use App\Models\ExamAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonalStatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_participant_sees_empty_statistics_without_demo_scores(): void
    {
        $user = User::factory()->create(['name' => 'Peserta Baru']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Peserta Baru')
            ->assertSee('Belum ada nilai tryout');
        $this->actingAs($user)->get(route('statistics.index'))
            ->assertOk()
            ->assertSee('Belum ada data tryout');
    }

    public function test_dashboard_and_statistics_only_use_the_signed_in_participants_results(): void
    {
        $user = User::factory()->create(['name' => 'Peserta Satu']);
        $other = User::factory()->create(['name' => 'Peserta Dua']);
        $ownAttempt = ExamAttempt::resumeOrStart($user, ExamBank::TryoutSkd5);
        $ownAttempt->answers = ['1' => 2];
        $ownAttempt->save();
        $ownAttempt->complete();

        $otherAttempt = ExamAttempt::resumeOrStart($other, ExamBank::TryoutSkd5);
        $otherAttempt->answers = ['1' => 2, '2' => 2, '3' => 1];
        $otherAttempt->save();
        $otherAttempt->complete();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('summary', fn (array $summary): bool => $summary['count'] === 1 && $summary['latest']->id === $ownAttempt->id);
        $this->actingAs($user)->get(route('statistics.index'))
            ->assertOk()
            ->assertViewHas('summary', fn (array $summary): bool => $summary['count'] === 1 && $summary['latest']->score === 5);
    }

    public function test_period_filter_excludes_older_results(): void
    {
        $user = User::factory()->create();
        $older = ExamAttempt::resumeOrStart($user, ExamBank::QuizSilogisme);
        $older->complete();
        $older->update(['submitted_at' => now()->subDays(45)]);
        $recent = ExamAttempt::resumeOrStart($user, ExamBank::QuizSilogisme);
        $recent->complete();

        $this->actingAs($user)->get(route('statistics.index', ['kind' => 'quiz', 'period' => '30']))
            ->assertOk()
            ->assertViewHas('summary', fn (array $summary): bool => $summary['count'] === 1 && $summary['latest']->id === $recent->id);
        $this->actingAs($user)->get(route('statistics.index', ['kind' => 'quiz', 'period' => 'all']))
            ->assertOk()
            ->assertViewHas('summary', fn (array $summary): bool => $summary['count'] === 2);
    }

    public function test_history_does_not_include_another_participants_attempt(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $ownAttempt = ExamAttempt::resumeOrStart($user, ExamBank::QuizSilogisme);
        $ownAttempt->complete();
        $otherAttempt = ExamAttempt::resumeOrStart($other, ExamBank::TryoutSkd5);
        $otherAttempt->complete();

        $this->actingAs($user)->get(route('history.index'))
            ->assertOk()
            ->assertSee($ownAttempt->title)
            ->assertDontSee($otherAttempt->title);
    }
}
