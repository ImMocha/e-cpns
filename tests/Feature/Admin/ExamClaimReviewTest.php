<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Exam;
use App\Models\ExamClaim;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ExamClaimReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_list_keeps_all_submissions_after_rejection(): void
    {
        $claim = ExamClaim::factory()->create(['exam_title' => 'Pengajuan yang ditolak']);
        $approved = ExamClaim::factory()->create(['status' => 'approved', 'exam_title' => 'Pengajuan yang diterima']);
        $pending = ExamClaim::factory()->create(['exam_title' => 'Pengajuan butuh verifikasi']);
        $this->actingAs(User::factory()->admin()->create())->put(route('admin.exam-claims.update', $claim), [
            'status' => 'rejected', 'review_note' => 'Bukti tidak lengkap.',
        ])->assertSessionHasNoErrors();

        $this->get(route('admin.exam-claims.index'))->assertOk()
            ->assertViewHas('status', 'all')
            ->assertViewHas('claims', fn ($claims): bool => $claims->total() === 3)
            ->assertSee($claim->exam_title)->assertSee($approved->exam_title)->assertSee($pending->exam_title)
            ->assertSee('Semua pengajuan')->assertSee('Butuh verifikasi')->assertSee('Diterima')->assertSee('Ditolak');
        $this->assertDatabaseHas('exam_claims', ['id' => $claim->id, 'status' => 'rejected', 'review_note' => 'Bukti tidak lengkap.']);
    }

    /** @return array<string, array{string}> */
    public static function statuses(): array
    {
        return ['all' => ['all'], 'needs review' => ['pending'], 'accepted' => ['approved'], 'rejected' => ['rejected']];
    }

    #[DataProvider('statuses')]
    public function test_status_filter_returns_only_matching_submissions(string $status): void
    {
        $claims = ExamClaim::factory()->count(3)->sequence(
            ['status' => 'pending'], ['status' => 'approved'], ['status' => 'rejected'],
        )->create();
        $expectedIds = $status === 'all'
            ? [$claims[2]->id, $claims[1]->id, $claims[0]->id]
            : [$claims->firstWhere('status', $status)->id];

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.exam-claims.index', ['status' => $status]))
            ->assertOk()->assertViewHas('claims', fn ($results): bool => $results->pluck('id')->all() === $expectedIds);
    }

    public function test_filter_is_preserved_when_paging_through_rejected_submissions(): void
    {
        $user = User::factory()->create();
        $exam = Exam::factory()->tryout()->claimable()->create();
        $claims = ExamClaim::factory()->count(16)->for($user)->for($exam)->create(['status' => 'rejected']);
        ExamClaim::factory()->for($user)->for($exam)->create(['status' => 'pending']);
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.exam-claims.index', ['status' => 'rejected']))
            ->assertViewHas('claims', fn ($results): bool => $results->total() === 16 && str_contains($results->url(2), 'status=rejected'));
        $this->get(route('admin.exam-claims.index', ['status' => 'rejected', 'page' => 2]))
            ->assertOk()->assertViewHas('claims', fn ($results): bool => $results->pluck('id')->all() === [$claims[0]->id]);
    }

    public function test_invalid_status_is_rejected(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.exam-claims.index', ['status' => 'invalid']))
            ->assertSessionHasErrors('status');
    }

    public function test_detail_shows_earlier_submissions_from_same_user_across_sessions_only(): void
    {
        $user = User::factory()->create();
        $reviewer = User::factory()->admin()->create(['name' => 'Admin Pemeriksa Sebelumnya']);
        $first = ExamClaim::factory()->for($user)->create([
            'exam_title' => 'Tryout terdahulu', 'status' => 'rejected',
            'review_note' => 'Screenshot follow belum terlihat.', 'customer_note' => 'Bukti pertama.',
            'proof_paths' => ['exam-claims/history-proof.png'], 'reviewed_by' => $reviewer->id, 'reviewed_at' => now(),
        ]);
        $second = ExamClaim::factory()->for($user)->create(['exam_title' => 'Sesi lain diterima', 'status' => 'approved']);
        ExamClaim::factory()->create(['exam_title' => 'Pengajuan milik orang lain']);
        $current = ExamClaim::factory()->for($user)->create(['exam_title' => 'Pengajuan saat ini']);
        ExamClaim::factory()->for($user)->create(['exam_title' => 'Pengajuan setelahnya']);

        $this->actingAs($reviewer)->get(route('admin.exam-claims.show', $current))->assertOk()
            ->assertViewHas('history', fn ($history): bool => $history->pluck('id')->all() === [$second->id, $first->id])
            ->assertSee('Riwayat pengajuan peserta')->assertSee($first->exam_title)->assertSee($second->exam_title)
            ->assertSee('Screenshot follow belum terlihat.')->assertSee('Bukti pertama.')->assertSee($reviewer->name)
            ->assertSee(route('admin.exam-claims.show', $first), false)
            ->assertSee(route('exam-claims.proof', [$first, 0]), false)
            ->assertDontSee('Pengajuan milik orang lain')->assertDontSee('Pengajuan setelahnya');
    }

    public function test_history_is_paginated_and_does_not_duplicate_the_current_submission(): void
    {
        $user = User::factory()->create();
        $exam = Exam::factory()->tryout()->claimable()->create();
        $previous = ExamClaim::factory()->count(11)->for($user)->for($exam)->create(['status' => 'rejected']);
        $current = ExamClaim::factory()->for($user)->for($exam)->create();

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.exam-claims.show', $current))
            ->assertViewHas('history', fn ($history): bool => $history->total() === 11 && $history->count() === 10
                && ! $history->contains('id', $current->id));
        $this->get(route('admin.exam-claims.show', [$current, 'history_page' => 2]))
            ->assertOk()->assertViewHas('history', fn ($history): bool => $history->pluck('id')->all() === [$previous[0]->id]);
    }

    public function test_first_submission_has_an_empty_history_message(): void
    {
        $claim = ExamClaim::factory()->create();
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.exam-claims.show', $claim))
            ->assertOk()->assertSee('Peserta ini belum memiliki pengajuan sebelumnya.')
            ->assertViewHas('history', fn ($history): bool => $history->isEmpty());
    }

    public function test_history_escapes_notes_and_remains_admin_only(): void
    {
        $user = User::factory()->create();
        $previous = ExamClaim::factory()->for($user)->create([
            'review_note' => '<script>alert(1)</script>', 'customer_note' => '<script>alert(2)</script>',
        ]);
        $current = ExamClaim::factory()->for($user)->create();

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.exam-claims.show', $current))
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertSee('&lt;script&gt;alert(2)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('<script>alert(2)</script>', false);
        $this->actingAs($user)->get(route('admin.exam-claims.show', $current))->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('admin.exam-claims.show', $previous))->assertForbidden();
    }
}
