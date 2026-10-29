<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Material;
use App\Models\Package;
use App\Models\Question;
use App\Models\Topic;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TryoutTokenTest extends TestCase
{
    use RefreshDatabase;

    private function tryout(): Exam
    {
        $exam = Exam::factory()->tryout()->create(['access_level' => 'premium']);
        $exam->questions()->attach(Question::factory()->published()->create());

        return $exam;
    }

    private function grant(User $user): UserPackage
    {
        $package = Package::factory()->create(['tryout_tokens' => 1, 'tryout_quota' => 2]);

        return UserPackage::grant($user, $package, User::factory()->admin()->create());
    }

    private function completeTryout(Exam $exam): ExamAttempt
    {
        $this->post(route('tryouts.start', $exam->slug))->assertRedirect();
        $attempt = ExamAttempt::query()->latest('id')->firstOrFail();
        $this->post(route('exam-attempts.submit', $attempt))->assertOk();

        return $attempt;
    }

    /** @return array<string, array{bool}> */
    public static function repeatPurchases(): array
    {
        return ['same package' => [true], 'different package' => [false]];
    }

    #[DataProvider('repeatPurchases')]
    public function test_token_from_another_purchase_refills_exhausted_tryout_and_keeps_history(bool $samePackage): void
    {
        $this->freezeTime();
        $exam = $this->tryout();
        $user = User::factory()->create();
        $old = $this->grant($user);
        $this->actingAs($user)->post(route('tryout-tokens.redeem', [$old, $exam]))->assertSessionHasNoErrors();
        $first = $this->completeTryout($exam);
        $second = $this->completeTryout($exam);
        $package = $samePackage ? $old->package : Package::factory()->create(['tryout_tokens' => 1]);
        $new = UserPackage::factory()->for($user)->for($package)->create(['tryout_tokens' => 1, 'tryout_quota' => 2]);

        $this->get(route('tryouts.index'))->assertSee('Isi ulang dengan 1 token');
        $this->post(route('tryouts.start', $exam->slug))->assertInvalid(['exam' => 'Kuota tryout paket sudah habis.']);
        $this->post(route('tryout-tokens.redeem', [$new, $exam]))
            ->assertRedirect(route('tryouts.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('user_packages', ['id' => $old->id, 'tryout_tokens' => 0, 'tryouts_used' => 2]);
        $this->assertDatabaseHas('user_packages', ['id' => $new->id, 'tryout_tokens' => 0, 'tryouts_used' => 0]);
        $this->assertDatabaseCount('exam_user_package', 2);
        $this->get(route('tryouts.index'))->assertSee('Kuota tersisa: 2');
        $this->post(route('tryout-tokens.redeem', [$new, $exam]))->assertSessionHasNoErrors();
        $third = $this->completeTryout($exam);
        $fourth = $this->completeTryout($exam);
        $this->post(route('tryouts.start', $exam->slug))->assertInvalid(['exam' => 'Kuota tryout paket sudah habis.']);

        $this->assertDatabaseHas('exam_attempts', ['id' => $first->id, 'status' => ExamAttempt::COMPLETED, 'user_package_id' => $old->id]);
        $this->assertDatabaseHas('exam_attempts', ['id' => $second->id, 'status' => ExamAttempt::COMPLETED, 'user_package_id' => $old->id]);
        $this->assertDatabaseHas('exam_attempts', ['id' => $third->id, 'user_package_id' => $new->id]);
        $this->assertDatabaseHas('exam_attempts', ['id' => $fourth->id, 'user_package_id' => $new->id]);
        $this->assertDatabaseHas('user_packages', ['id' => $new->id, 'tryout_tokens' => 0, 'tryouts_used' => 2]);
        $this->assertDatabaseCount('exam_attempts', 4);
        $this->get($first->resultUrl())->assertOk();
    }

    public function test_customer_can_use_new_purchase_for_another_tryout_while_old_access_has_attempts(): void
    {
        $firstExam = $this->tryout();
        $secondExam = $this->tryout();
        $user = User::factory()->create();
        $old = $this->grant($user);
        $this->actingAs($user)->post(route('tryout-tokens.redeem', [$old, $firstExam]))->assertSessionHasNoErrors();
        $new = UserPackage::factory()->for($user)->for($old->package)->create(['tryout_tokens' => 1]);

        $this->post(route('tryout-tokens.redeem', [$new, $secondExam]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('user_packages', ['id' => $old->id, 'tryout_tokens' => 0, 'tryouts_used' => 0]);
        $this->assertDatabaseHas('user_packages', ['id' => $new->id, 'tryout_tokens' => 0, 'tryouts_used' => 0]);
        $this->assertDatabaseHas('exam_user_package', ['exam_id' => $firstExam->id, 'user_package_id' => $old->id]);
        $this->assertDatabaseHas('exam_user_package', ['exam_id' => $secondExam->id, 'user_package_id' => $new->id]);
        $this->completeTryout($firstExam);
        $this->completeTryout($secondExam);
        $this->assertDatabaseHas('user_packages', ['id' => $old->id, 'tryouts_used' => 1]);
        $this->assertDatabaseHas('user_packages', ['id' => $new->id, 'tryouts_used' => 1]);
    }

    public function test_running_last_attempt_blocks_refill_until_it_expires(): void
    {
        $this->freezeTime();
        $exam = $this->tryout();
        $user = User::factory()->create();
        $old = $this->grant($user);
        $this->actingAs($user)->post(route('tryout-tokens.redeem', [$old, $exam]))->assertSessionHasNoErrors();
        $this->completeTryout($exam);
        $this->post(route('tryouts.start', $exam->slug))->assertRedirect();
        $running = ExamAttempt::query()->latest('id')->firstOrFail();
        $new = UserPackage::factory()->for($user)->for($old->package)->create(['tryout_tokens' => 1]);

        $this->post(route('tryout-tokens.redeem', [$new, $exam]))
            ->assertInvalid(['exam' => 'Selesaikan sesi tryout yang masih berjalan sebelum menggunakan token lagi.']);

        $this->assertDatabaseHas('user_packages', ['id' => $new->id, 'tryout_tokens' => 1]);
        $this->assertDatabaseMissing('exam_user_package', ['exam_id' => $exam->id, 'user_package_id' => $new->id]);
        $this->travel(31)->minutes();
        $this->post(route('tryout-tokens.redeem', [$new, $exam]))->assertSessionHasNoErrors();
        $this->post(route('tryouts.start', $exam->slug))->assertRedirect();
        $this->assertDatabaseHas('exam_attempts', ['id' => $running->id, 'status' => ExamAttempt::COMPLETED, 'auto_submitted' => true]);
        $this->assertDatabaseHas('user_packages', ['id' => $new->id, 'tryout_tokens' => 0, 'tryouts_used' => 1]);
    }

    public function test_active_unlimited_access_prevents_spending_a_token_to_refill(): void
    {
        $exam = $this->tryout();
        $user = User::factory()->create();
        $old = $this->grant($user);
        $old->update(['tryout_quota' => null]);
        $old->exams()->attach($exam);
        $new = UserPackage::factory()->for($user)->for($old->package)->create(['tryout_tokens' => 1]);

        $this->actingAs($user)->post(route('tryout-tokens.redeem', [$new, $exam]))
            ->assertInvalid(['exam' => 'Tryout ini masih memiliki kuota percobaan. Gunakan percobaan yang tersedia terlebih dahulu.']);

        $this->assertDatabaseHas('user_packages', ['id' => $new->id, 'tryout_tokens' => 1]);
        $this->assertDatabaseMissing('exam_user_package', ['exam_id' => $exam->id, 'user_package_id' => $new->id]);
        $this->get(route('tryouts.index'))->assertDontSee('Isi ulang dengan 1 token');
    }

    public function test_another_purchase_cannot_spend_a_second_token_after_tryout_was_refilled(): void
    {
        $exam = $this->tryout();
        $user = User::factory()->create();
        $old = $this->grant($user);
        $old->exams()->attach($exam);
        $old->update(['tryout_tokens' => 0, 'tryouts_used' => 2]);
        $firstNew = UserPackage::factory()->for($user)->for($old->package)->create(['tryout_tokens' => 1]);
        $secondNew = UserPackage::factory()->for($user)->for($old->package)->create(['tryout_tokens' => 1]);

        $this->actingAs($user)->post(route('tryout-tokens.redeem', [$firstNew, $exam]))->assertSessionHasNoErrors();
        $this->post(route('tryout-tokens.redeem', [$secondNew, $exam]))
            ->assertInvalid(['exam' => 'Tryout ini masih memiliki kuota percobaan. Gunakan percobaan yang tersedia terlebih dahulu.']);

        $this->assertDatabaseHas('user_packages', ['id' => $firstNew->id, 'tryout_tokens' => 0]);
        $this->assertDatabaseHas('user_packages', ['id' => $secondNew->id, 'tryout_tokens' => 1]);
        $this->assertDatabaseMissing('exam_user_package', ['exam_id' => $exam->id, 'user_package_id' => $secondNew->id]);
        $this->assertDatabaseCount('exam_user_package', 2);
    }

    public function test_customer_chooses_one_tryout_and_can_attempt_it_twice(): void
    {
        $first = $this->tryout();
        $chosen = $this->tryout();
        $user = User::factory()->create();
        $grant = $this->grant($user);

        $this->actingAs($user)->get(route('tryouts.index'))->assertSee('1 token tersedia')->assertSee('Buka dengan 1 token');
        $this->post(route('tryouts.start', $chosen->slug))->assertForbidden();
        $this->post(route('tryout-tokens.redeem', [$grant, $chosen]), ['tryout_tokens' => 99])
            ->assertRedirect(route('tryouts.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('exam_user_package', ['exam_id' => $chosen->id, 'user_package_id' => $grant->id]);
        $this->assertDatabaseMissing('exam_user_package', ['exam_id' => $first->id, 'user_package_id' => $grant->id]);
        $this->assertDatabaseHas('user_packages', ['id' => $grant->id, 'tryout_tokens' => 0, 'tryouts_used' => 0]);
        $this->assertDatabaseCount('exam_attempts', 0);

        $this->post(route('tryout-tokens.redeem', [$grant, $first]))
            ->assertInvalid(['exam' => 'Token atau kuota percobaan paket sudah habis.']);
        $this->post(route('tryouts.start', $first->slug))->assertForbidden();
        $this->post(route('tryouts.start', $chosen->slug))->assertRedirect();
        $firstAttempt = ExamAttempt::sole();
        $this->post(route('tryouts.start', $chosen->slug))->assertRedirect($firstAttempt->attemptUrl());
        $this->assertDatabaseHas('user_packages', ['id' => $grant->id, 'tryouts_used' => 1]);
        $this->post(route('exam-attempts.submit', $firstAttempt))->assertOk();
        $this->post(route('tryouts.start', $chosen->slug))->assertRedirect();
        $secondAttempt = ExamAttempt::query()->latest('id')->firstOrFail();
        $this->post(route('exam-attempts.submit', $secondAttempt))->assertOk();
        $this->post(route('tryouts.start', $chosen->slug))->assertInvalid(['exam' => 'Kuota tryout paket sudah habis.']);

        $this->assertDatabaseCount('exam_attempts', 2);
        $this->assertDatabaseCount('exam_user_package', 1);
        $this->assertDatabaseHas('user_packages', ['id' => $grant->id, 'tryout_tokens' => 0, 'tryouts_used' => 2]);
        $this->get($secondAttempt->resultUrl())->assertSee('Pembahasan');
    }

    public function test_repeated_redemption_does_not_spend_another_token(): void
    {
        $exam = $this->tryout();
        $user = User::factory()->create();
        $grant = $this->grant($user);
        $grant->update(['tryout_tokens' => 2]);

        $this->actingAs($user)->post(route('tryout-tokens.redeem', [$grant, $exam]))->assertSessionHasNoErrors();
        $this->post(route('tryout-tokens.redeem', [$grant, $exam]))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('exam_user_package', 1);
        $this->assertDatabaseHas('user_packages', ['id' => $grant->id, 'tryout_tokens' => 1, 'tryouts_used' => 0]);
    }

    public function test_guest_and_other_customer_cannot_redeem_another_accounts_token(): void
    {
        $exam = $this->tryout();
        $grant = $this->grant(User::factory()->create());

        $this->post(route('tryout-tokens.redeem', [$grant, $exam]))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->post(route('tryout-tokens.redeem', [$grant, $exam]))->assertNotFound();
        $this->actingAs(User::factory()->admin()->create())->post(route('tryout-tokens.redeem', [$grant, $exam]))->assertNotFound();

        $this->assertDatabaseCount('exam_user_package', 0);
        $this->assertDatabaseHas('user_packages', ['id' => $grant->id, 'tryout_tokens' => 1]);
    }

    /** @return array<string, array{array<string, mixed>}> */
    public static function inactivePackages(): array
    {
        return ['expired' => [['expires_at' => '2025-01-01']],
            'revoked' => [['revoked_at' => '2025-01-01']],
            'not yet active' => [['starts_at' => '2030-01-01']]];
    }

    #[DataProvider('inactivePackages')]
    public function test_inactive_package_cannot_redeem_tokens(array $changes): void
    {
        $this->travelTo('2026-10-28 10:00:00');
        $exam = $this->tryout();
        $user = User::factory()->create();
        $grant = $this->grant($user);
        $grant->update($changes);

        $this->actingAs($user)->post(route('tryout-tokens.redeem', [$grant, $exam]))->assertForbidden();

        $this->assertDatabaseCount('exam_user_package', 0);
        $this->assertDatabaseHas('user_packages', ['id' => $grant->id, 'tryout_tokens' => 1]);
    }

    /** @return array<string, array{array<string, mixed>}> */
    public static function unavailableExams(): array
    {
        return ['draft' => [['status' => 'draft']], 'free' => [['access_level' => 'free']],
            'practice' => [['kind' => 'practice']], 'conditional free' => [['free_claim_enabled' => true]],
            'insufficient questions' => [['question_count' => 2]], 'deleted' => [['deleted_at' => '2026-01-01']]];
    }

    #[DataProvider('unavailableExams')]
    public function test_unavailable_exam_cannot_consume_a_token(array $changes): void
    {
        $exam = $this->tryout();
        $user = User::factory()->create();
        $grant = $this->grant($user);
        $exam->forceFill($changes)->save();

        $this->actingAs($user)->post(route('tryout-tokens.redeem', [$grant, $exam]))->assertNotFound();

        $this->assertDatabaseCount('exam_user_package', 0);
        $this->assertDatabaseHas('user_packages', ['id' => $grant->id, 'tryout_tokens' => 1]);
    }

    public function test_quota_exhaustion_and_existing_access_do_not_waste_tokens(): void
    {
        $exam = $this->tryout();
        $user = User::factory()->create();
        $grant = $this->grant($user);
        $anotherGrant = $this->grant($user);
        $grant->exams()->attach($exam);

        $this->actingAs($user)->post(route('tryout-tokens.redeem', [$anotherGrant, $exam]))
            ->assertInvalid(['exam' => 'Tryout ini masih memiliki kuota percobaan. Gunakan percobaan yang tersedia terlebih dahulu.']);
        $grant->exams()->detach();
        $anotherGrant->update(['tryouts_used' => 2]);
        $this->post(route('tryout-tokens.redeem', [$anotherGrant, $exam]))
            ->assertInvalid(['exam' => 'Token atau kuota percobaan paket sudah habis.']);

        $this->assertDatabaseCount('exam_user_package', 0);
        $this->assertDatabaseHas('user_packages', ['id' => $anotherGrant->id, 'tryout_tokens' => 1]);
    }

    public function test_admin_can_sell_a_token_package_without_preselecting_a_tryout(): void
    {
        $this->tryout();
        $payload = ['name' => 'Paket Reguler', 'price' => 15000, 'duration_days' => 365,
            'tryout_tokens' => 1, 'tryout_quota' => 2, 'position' => 1, 'is_active' => true,
            'material_ids' => [], 'exam_ids' => []];

        $this->actingAs(User::factory()->admin()->create())->post(route('admin.packages.store'), $payload)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('packages', ['name' => 'Paket Reguler', 'tryout_tokens' => 1, 'tryout_quota' => 2]);
        $this->assertDatabaseCount('exam_package', 0);
        $this->get(route('home'))->assertSee('1 token untuk 1 tryout pilihanmu');
        $this->actingAs(User::factory()->create())->get(route('checkout', ['package' => Package::sole()->id]))
            ->assertSee('1 token pilihan tryout')->assertDontSee('Daftar konten belum tersedia.');
    }

    public function test_token_package_cannot_be_sold_without_eligible_tryouts_or_attempt_quota(): void
    {
        $payload = ['name' => 'Paket Reguler', 'price' => 15000, 'tryout_tokens' => 1, 'tryout_quota' => 2,
            'position' => 1, 'is_active' => true, 'material_ids' => [], 'exam_ids' => []];

        $this->actingAs(User::factory()->admin()->create())->post(route('admin.packages.store'), $payload)
            ->assertInvalid(['tryout_tokens' => 'Belum ada tryout premium terbit yang dapat dibuka dengan token.']);
        $this->tryout();
        $this->post(route('admin.packages.store'), array_replace($payload, ['tryout_quota' => 0]))
            ->assertInvalid(['tryout_quota']);

        $this->assertDatabaseCount('packages', 0);
    }

    public function test_unavailable_questions_disable_checkout_without_spending_existing_tokens(): void
    {
        $exam = $this->tryout();
        $user = User::factory()->create();
        $grant = $this->grant($user);
        $package = Package::factory()->create(['tryout_tokens' => 1]);
        $exam->questions()->firstOrFail()->update(['status' => 'draft']);

        $this->actingAs($user)->get(route('checkout', ['package' => $package->id]))
            ->assertSee('Paket ini belum bisa dibeli');
        $this->post(route('checkout.store'), ['package_id' => $package->id])
            ->assertInvalid(['package_id' => 'Konten paket sedang tidak tersedia.']);
        $this->post(route('tryout-tokens.redeem', [$grant, $exam]))->assertNotFound();

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('exam_user_package', 0);
        $this->assertDatabaseHas('user_packages', ['id' => $grant->id, 'tryout_tokens' => 1]);
    }

    public function test_topic_completion_requirement_does_not_spend_tokens_before_materials_are_complete(): void
    {
        $exam = $this->tryout();
        $topic = Topic::factory()->create();
        Material::factory()->video()->for($topic)->create(['section_id' => $topic->section_id]);
        $exam->update(['topic_id' => $topic->id, 'requires_topic_completion' => true]);
        $user = User::factory()->create();
        $grant = $this->grant($user);

        $this->actingAs($user)->post(route('tryout-tokens.redeem', [$grant, $exam]))
            ->assertInvalid(['exam' => 'Selesaikan semua materi pada topik ini sebelum membuka tryout.']);

        $this->assertDatabaseCount('exam_user_package', 0);
        $this->assertDatabaseHas('user_packages', ['id' => $grant->id, 'tryout_tokens' => 1]);
    }

    public function test_free_checkout_issues_tokens_without_opening_any_tryout(): void
    {
        $this->tryout();
        $package = Package::factory()->create(['price' => 0, 'tryout_tokens' => 1]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('checkout.store'), ['package_id' => $package->id, 'tryout_tokens' => 99])
            ->assertSessionHasNoErrors();

        $transaction = Transaction::sole();
        $this->assertSame(1, $transaction->package_snapshot['tryout_tokens']);
        $this->assertDatabaseHas('user_packages', ['id' => $transaction->user_package_id, 'tryout_tokens' => 1, 'tryouts_used' => 0]);
        $this->assertDatabaseCount('exam_user_package', 0);
        $this->get(route('packages.index'))->assertSee('1 token pilihan tryout tersisa');
        $this->get(route('transactions.show', $transaction))->assertSee('1 token pilihan tryout');
    }
}
