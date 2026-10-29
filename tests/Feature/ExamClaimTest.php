<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ExamKind;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamClaim;
use App\Models\Package;
use App\Models\Question;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ExamClaimTest extends TestCase
{
    use RefreshDatabase;

    private function exam(string $kind = 'tryout'): Exam
    {
        $exam = Exam::factory()->claimable()->create(['kind' => $kind, 'duration_minutes' => 30]);
        $exam->questions()->attach(Question::factory()->published()->create());

        return $exam;
    }

    /** @return array<string, mixed> */
    private function submission(Exam $exam): array
    {
        return [
            'instagram_username' => 'peserta_belajar', 'customer_note' => 'Bukti follow dan Story.',
            'proofs' => [UploadedFile::fake()->image('follow.png'), UploadedFile::fake()->image('story.jpg')],
            'terms_version' => $exam->freeClaimTermsVersion(), 'accept_terms' => '1',
        ];
    }

    /** @return array<string, mixed> */
    private function settings(Exam $exam): array
    {
        return [
            'title' => $exam->title, 'slug' => $exam->slug, 'kind' => $exam->kind,
            'access_level' => 'conditional', 'free_claim_terms' => 'Upload poster dan follow akun kami.',
            'free_claim_instagram_url' => 'https://www.instagram.com/ganeshanusa/',
            'question_count' => 1, 'duration_minutes' => 30, 'points_per_correct' => 5,
            'penalty_wrong' => 0, 'random_questions' => 1, 'random_options' => 1, 'status' => 'published',
            'question_ids' => $exam->questions()->pluck('questions.id')->all(),
        ];
    }

    /** @return array<string, array{string}> */
    public static function kinds(): array
    {
        return ['tryout' => ['tryout'], 'latihan soal' => ['practice']];
    }

    #[DataProvider('kinds')]
    public function test_admin_manages_conditional_access_and_can_switch_back_to_premium(string $kind): void
    {
        $exam = $this->exam($kind);
        $prefix = ExamKind::from($kind)->adminRoutePrefix();
        $this->actingAs(User::factory()->admin()->create())->get(route($prefix.'.edit', $exam))
            ->assertOk()->assertSee('value="conditional"', false)->assertSee('Syarat unlock gratis');

        $this->put(route($prefix.'.update', $exam), $this->settings($exam))->assertSessionHasNoErrors();
        $this->assertTrue($exam->fresh()->free_claim_enabled);
        $this->assertSame('free', $exam->fresh()->access_level->value);
        $this->assertSame('Upload poster dan follow akun kami.', $exam->fresh()->free_claim_terms);
        $this->get(route($prefix.'.edit', $exam))->assertSee('value="conditional" selected', false);
        $this->put(route($prefix.'.update', $exam), array_replace($this->settings($exam), ['access_level' => 'premium']))
            ->assertSessionHasNoErrors();
        $this->assertFalse($exam->fresh()->free_claim_enabled);
        $this->assertSame('premium', $exam->fresh()->access_level->value);
        $this->put(route($prefix.'.update', $exam), array_replace($this->settings($exam), ['access_level' => 'free']))
            ->assertSessionHasErrors('access_level');
        $this->actingAs(User::factory()->create())->post(route(ExamKind::from($kind)->routePrefix().'.start', $exam->slug))
            ->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->put(route($prefix.'.update', $exam), $this->settings($exam))
            ->assertSessionHasNoErrors();
        $this->assertTrue($exam->fresh()->free_claim_enabled);
        $this->assertSame('free', $exam->fresh()->access_level->value);
    }

    public function test_enabling_conditions_requires_terms_and_a_real_instagram_profile_url(): void
    {
        $exam = $this->exam();
        $this->actingAs(User::factory()->admin()->create())->put(route('admin.exams.update', $exam),
            array_replace($this->settings($exam), ['free_claim_terms' => '', 'free_claim_instagram_url' => '']))
            ->assertSessionHasErrors(['free_claim_terms', 'free_claim_instagram_url']);
        $this->put(route('admin.exams.update', $exam), array_replace($this->settings($exam), [
            'free_claim_instagram_url' => 'https://instagram.com.evil.example/account/',
        ]))->assertSessionHasErrors('free_claim_instagram_url');
        $this->assertSame('https://www.instagram.com/ganeshanusa/', $exam->fresh()->free_claim_instagram_url);
    }

    #[DataProvider('kinds')]
    public function test_admin_can_create_a_new_conditional_session(string $kind): void
    {
        $question = Question::factory()->published()->create();
        $exam = Exam::factory()->tryout()->make(['title' => 'Tryout Gratis Instagram', 'slug' => 'tryout-gratis-instagram']);
        $payload = [
            'title' => $exam->title, 'slug' => $exam->slug, 'kind' => $kind, 'access_level' => 'conditional',
            'free_claim_terms' => 'Follow dan upload poster.', 'free_claim_instagram_url' => 'https://www.instagram.com/ganeshanusa/',
            'question_count' => 1, 'duration_minutes' => 30, 'points_per_correct' => 5, 'penalty_wrong' => 0,
            'random_questions' => 1, 'random_options' => 1, 'status' => 'published', 'question_ids' => [$question->id],
        ];
        $this->actingAs(User::factory()->admin()->create())->post(route(ExamKind::from($kind)->adminRoutePrefix().'.store'), $payload)
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('exams', ['slug' => $exam->slug, 'kind' => $kind, 'access_level' => 'free', 'free_claim_enabled' => true,
            'free_claim_terms' => 'Follow dan upload poster.', 'free_claim_instagram_url' => 'https://www.instagram.com/ganeshanusa/']);
        $this->assertDatabaseCount('packages', 0);
    }

    public function test_topic_quizzes_do_not_accept_conditional_access(): void
    {
        $exam = $this->exam('quiz');
        $exam->update(['free_claim_enabled' => false]);
        $this->actingAs(User::factory()->admin()->create())->put(route('admin.materials.quizzes.update', $exam), $this->settings($exam))
            ->assertSessionHasErrors('access_level');
        $this->assertFalse($exam->fresh()->free_claim_enabled);
    }

    #[DataProvider('kinds')]
    public function test_locked_session_appears_in_its_catalog_and_cannot_be_started_directly(string $kind): void
    {
        $exam = $this->exam($kind);
        $user = User::factory()->create();
        $catalog = ExamKind::from($kind)->catalogRoute();
        $this->actingAs($user)->get(route($catalog))->assertOk()->assertSee('Gratis Bersyarat')->assertSee('Unlock gratis')
            ->assertSee(route('exam-claims.create', $exam), false)->assertDontSee('Memerlukan paket aktif');
        $this->get(route('exam-claims.create', $exam))->assertSee($exam->free_claim_terms)->assertSee('Kirim bukti untuk unlock');
        $this->post(route(ExamKind::from($kind)->routePrefix().'.start', $exam->slug))->assertForbidden();
        $this->assertDatabaseCount('exam_attempts', 0);
    }

    public function test_submission_stores_private_proofs_and_snapshots_without_creating_a_learning_package(): void
    {
        Storage::fake('local');
        $exam = $this->exam();
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('exam-claims.store', $exam), array_replace($this->submission($exam), [
            'status' => 'approved', 'user_id' => User::factory()->create()->id,
        ]))->assertSessionHasNoErrors()->assertRedirect(route('exam-claims.create', $exam));
        $claim = ExamClaim::sole();
        $this->assertSame($user->id, $claim->user_id);
        $this->assertSame($exam->id, $claim->exam_id);
        $this->assertSame('pending', $claim->status);
        $this->assertSame($exam->free_claim_terms, $claim->terms);
        $this->assertCount(2, $claim->proof_paths);
        Storage::disk('local')->assertExists($claim->proof_paths);
        $this->assertDatabaseCount('user_packages', 0);
        $this->assertDatabaseCount('transactions', 0);
        $this->get(route('tryouts.index'))->assertSee('Menunggu verifikasi admin')->assertSee('Lihat pengajuan');
        $this->get(route('exam-claims.create', $exam))->assertDontSee('Kirim bukti untuk unlock');
        $this->post(route('tryouts.start', $exam->slug))->assertForbidden();
    }

    #[DataProvider('kinds')]
    public function test_approval_unlocks_only_that_session_without_a_purchase_or_quota_usage(string $kind): void
    {
        $exam = $this->exam($kind);
        $excluded = $this->exam($kind);
        $claim = ExamClaim::factory()->for($exam)->create(['exam_title' => $exam->title, 'kind' => $kind]);
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('admin.exam-claims.index'))->assertSee($exam->title);
        $this->get(route('admin.exam-claims.show', $claim))->assertSee($claim->terms);
        $this->get(route('admin.dashboard'))->assertSee('1 unlock gratis menunggu');
        $this->put(route('admin.exam-claims.update', $claim), ['status' => 'approved', 'review_note' => 'Bukti sesuai.'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('exam_claims', ['id' => $claim->id, 'status' => 'approved', 'reviewed_by' => $admin->id]);
        $this->put(route('admin.exam-claims.update', $claim), ['status' => 'approved'])->assertSessionHasErrors('status');
        $this->assertDatabaseCount('user_packages', 0);
        $this->assertDatabaseCount('transactions', 0);

        $prefix = ExamKind::from($kind)->routePrefix();
        $this->actingAs($claim->user)->get(route(ExamKind::from($kind)->catalogRoute()))->assertSee('Sudah terbuka');
        $this->post(route($prefix.'.start', $exam->slug))->assertRedirect();
        $attempt = ExamAttempt::sole();
        $this->assertNull($attempt->user_package_id);
        $attempt->complete();
        $this->post(route($prefix.'.start', $exam->slug))->assertRedirect();
        $this->post(route($prefix.'.start', $excluded->slug))->assertForbidden();
        $this->assertDatabaseCount('exam_attempts', 2);
    }

    public function test_paid_package_does_not_bypass_unlock_or_lose_quota_after_approval(): void
    {
        $exam = $this->exam();
        $user = User::factory()->create();
        $package = Package::factory()->create();
        $package->exams()->attach($exam);
        $grant = UserPackage::grant($user, $package, User::factory()->admin()->create());
        $this->actingAs($user)->get(route('tryouts.index'))->assertSee('Unlock gratis');
        $this->post(route('tryouts.start', $exam->slug))->assertForbidden();
        ExamClaim::factory()->for($exam)->for($user)->create(['status' => 'approved']);
        $this->post(route('tryouts.start', $exam->slug))->assertRedirect();
        $this->assertSame(0, $grant->fresh()->tryouts_used);
        $this->assertNull(ExamAttempt::sole()->user_package_id);
    }

    public function test_rejection_requires_reason_and_allows_resubmission_preserving_history(): void
    {
        Storage::fake('local');
        $exam = $this->exam();
        $claim = ExamClaim::factory()->for($exam)->create();
        $this->actingAs(User::factory()->admin()->create())->put(route('admin.exam-claims.update', $claim), ['status' => 'rejected'])
            ->assertSessionHasErrors(['review_note' => 'Tuliskan alasan penolakan agar peserta dapat memperbaiki buktinya.']);
        $this->assertSame('pending', $claim->fresh()->status);
        $this->put(route('admin.exam-claims.update', $claim), ['status' => 'rejected', 'review_note' => 'Bukti Story belum terlihat.'])
            ->assertSessionHasNoErrors();
        $this->actingAs($claim->user)->get(route('tryouts.index'))->assertSee('Perbaiki bukti');
        $this->get(route('exam-claims.create', $exam))->assertSee('Bukti Story belum terlihat.');
        $this->post(route('exam-claims.store', $exam), $this->submission($exam))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('exam_claims', 2);
        $this->assertDatabaseHas('exam_claims', ['id' => $claim->id, 'status' => 'rejected']);
        Storage::disk('local')->assertExists(ExamClaim::query()->latest('id')->first()->proof_paths);
    }

    public function test_duplicate_pending_and_approved_claims_are_blocked_before_upload(): void
    {
        Storage::fake('local');
        $exam = $this->exam();
        $claim = ExamClaim::factory()->for($exam)->create();
        $this->actingAs($claim->user)->post(route('exam-claims.store', $exam), $this->submission($exam))->assertSessionHasErrors('proofs');
        $claim->update(['status' => 'approved']);
        $this->post(route('exam-claims.store', $exam), $this->submission($exam))->assertSessionHasErrors('proofs');
        $this->assertDatabaseCount('exam_claims', 1);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_proof_is_only_visible_to_owner_and_admin(): void
    {
        Storage::fake('local');
        $claim = ExamClaim::factory()->create(['proof_paths' => ['exam-claims/private.png']]);
        Storage::disk('local')->put('exam-claims/private.png', 'private-proof');
        $url = route('exam-claims.proof', [$claim, 0]);
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get($url)->assertNotFound();
        $this->actingAs($claim->user)->get($url)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get(route('exam-claims.proof', [$claim, 1]))->assertNotFound();
        $this->actingAs(User::factory()->admin()->create())->get($url)->assertOk();
        Storage::disk('local')->assertExists('exam-claims/private.png');
    }

    public function test_review_is_admin_only_and_admin_cannot_submit_participant_proof(): void
    {
        $claim = ExamClaim::factory()->create();
        $this->get(route('exam-claims.create', $claim->exam))->assertRedirect(route('login'));
        $this->post(route('exam-claims.store', $claim->exam), [])->assertRedirect(route('login'));
        $this->get(route('admin.exam-claims.index'))->assertRedirect(route('login'));
        $this->actingAs($claim->user)->get(route('admin.exam-claims.index'))->assertForbidden();
        $this->get(route('admin.exam-claims.show', $claim))->assertForbidden();
        $this->put(route('admin.exam-claims.update', $claim), ['status' => 'approved'])->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->post(route('exam-claims.store', $claim->exam), [])->assertForbidden();
        $this->assertSame('pending', $claim->fresh()->status);
    }

    /** @return array<string, array{string}> */
    public static function invalidProofs(): array
    {
        return ['missing' => ['missing'], 'too many' => ['many'], 'too large' => ['large'], 'active format' => ['svg'], 'document' => ['pdf']];
    }

    #[DataProvider('invalidProofs')]
    public function test_proof_limits_are_enforced_before_storage(string $case): void
    {
        Storage::fake('local');
        $exam = $this->exam();
        $payload = $this->submission($exam);
        $payload['proofs'] = match ($case) {
            'missing' => [],
            'many' => array_map(fn (int $index): UploadedFile => UploadedFile::fake()->image('proof'.$index.'.png'), range(1, 6)),
            'large' => [UploadedFile::fake()->image('proof.png')->size(4097)],
            'svg' => [UploadedFile::fake()->createWithContent('proof.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>')],
            'pdf' => [UploadedFile::fake()->create('proof.pdf', 20, 'application/pdf')],
        };
        $this->actingAs(User::factory()->create())->post(route('exam-claims.store', $exam), $payload)
            ->assertSessionHasErrors(in_array($case, ['large', 'svg', 'pdf'], true) ? 'proofs.0' : 'proofs');
        $this->assertDatabaseCount('exam_claims', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_username_and_consent_are_required(): void
    {
        Storage::fake('local');
        $exam = $this->exam();
        $this->actingAs(User::factory()->create())->post(route('exam-claims.store', $exam), [
            'instagram_username' => '@invalid name', 'proofs' => [UploadedFile::fake()->image('follow.png')],
            'terms_version' => $exam->freeClaimTermsVersion(),
        ])->assertSessionHasErrors(['instagram_username', 'accept_terms']);
        $this->assertDatabaseCount('exam_claims', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_changed_terms_require_new_consent_and_preserve_the_submitted_terms(): void
    {
        Storage::fake('local');
        $exam = $this->exam();
        $payload = $this->submission($exam);
        $exam->update(['free_claim_terms' => 'Syarat baru: upload ke feed.']);
        $this->actingAs(User::factory()->create())->post(route('exam-claims.store', $exam), $payload)->assertSessionHasErrors('proofs');
        $payload['terms_version'] = $exam->freeClaimTermsVersion();
        $this->post(route('exam-claims.store', $exam), $payload)->assertSessionHasNoErrors();
        $claim = ExamClaim::sole();
        $exam->update(['free_claim_terms' => 'Syarat berikutnya.']);
        $this->assertSame('Syarat baru: upload ke feed.', $claim->fresh()->terms);
        Storage::disk('local')->assertExists($claim->proof_paths);
    }

    /** @return array<string, array{string}> */
    public static function unavailableExams(): array
    {
        return ['disabled' => ['disabled'], 'draft' => ['draft'], 'archived' => ['archived'], 'no questions' => ['empty']];
    }

    #[DataProvider('unavailableExams')]
    public function test_unavailable_session_cannot_receive_a_submission_or_approval(string $state): void
    {
        Storage::fake('local');
        $exam = $this->exam();
        $claim = ExamClaim::factory()->for($exam)->create();
        $payload = $this->submission($exam);
        match ($state) {
            'disabled' => $exam->update(['free_claim_enabled' => false]),
            'draft' => $exam->update(['status' => 'draft']),
            'archived' => $exam->delete(),
            'empty' => $exam->questions()->detach(),
        };
        $response = $this->actingAs($claim->user)->post(route('exam-claims.store', $exam->id), $payload);
        if ($state === 'archived') {
            $response->assertNotFound();
        } else {
            $response->assertSessionHasErrors('exam');
        }
        $this->actingAs(User::factory()->admin()->create())->put(route('admin.exam-claims.update', $claim), ['status' => 'approved'])
            ->assertSessionHasErrors('exam');
        $this->assertSame('pending', $claim->fresh()->status);
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertDatabaseCount('exam_attempts', 0);
    }

    public function test_inactive_participant_cannot_be_approved_but_can_be_rejected(): void
    {
        $exam = $this->exam();
        $claim = ExamClaim::factory()->for($exam)->for(User::factory()->inactive())->create();
        $this->actingAs(User::factory()->admin()->create())->put(route('admin.exam-claims.update', $claim), ['status' => 'approved'])
            ->assertSessionHasErrors('status');
        $this->assertSame('pending', $claim->fresh()->status);
        $exam->update(['free_claim_enabled' => false]);
        $this->put(route('admin.exam-claims.update', $claim), ['status' => 'rejected', 'review_note' => 'Akun tidak aktif.'])
            ->assertSessionHasNoErrors();
        $this->assertSame('rejected', $claim->fresh()->status);
    }

    public function test_existing_attempt_can_continue_after_conditions_are_enabled(): void
    {
        $exam = $this->exam();
        $exam->update(['free_claim_enabled' => false]);
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('tryouts.start', $exam->slug))->assertRedirect();
        $attempt = ExamAttempt::sole();
        $exam->update(['free_claim_enabled' => true]);
        $this->post(route('tryouts.start', $exam->slug))->assertRedirect($attempt->attemptUrl());
        $this->get($attempt->attemptUrl())->assertOk();
        $this->assertDatabaseCount('exam_attempts', 1);
    }

    public function test_terms_and_notes_are_escaped_and_learning_packages_have_no_unlock_controls(): void
    {
        $exam = $this->exam();
        $exam->update(['free_claim_terms' => '<script>alert(1)</script>']);
        $claim = ExamClaim::factory()->for($exam)->create(['customer_note' => '<script>alert(2)</script>']);
        $this->actingAs($claim->user)->get(route('exam-claims.create', $exam))
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->get(route('packages.index'))->assertDontSee('Klaim gratis')->assertDontSee($exam->title);
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.exam-claims.show', $claim))
            ->assertSee('&lt;script&gt;alert(2)&lt;/script&gt;', false)->assertDontSee('<script>alert(2)</script>', false);
        $this->get(route('admin.packages.create'))->assertDontSee('Klaim gratis bersyarat')->assertDontSee('name="free_claim_enabled"', false);
        $this->get('/admin/package-claims')->assertNotFound();
    }
}
