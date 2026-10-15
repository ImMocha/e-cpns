<?php

namespace Tests\Feature;

use App\Models\ExamAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class CertificateTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_certificates(): void
    {
        $this->get(route('certificates.index'))->assertRedirect(route('login'));
        $this->get(route('certificates.show', 1))->assertRedirect(route('login'));
    }

    public function test_list_contains_only_the_members_passed_tryouts(): void
    {
        $user = User::factory()->create();
        $completed = ExamAttempt::factory()->passingTryout()->for($user)->create(['title' => 'Tryout peserta']);
        $completed->complete();
        ExamAttempt::factory()->passingTryout()->for($user)->create(['title' => 'Tryout berjalan']);
        $quiz = ExamAttempt::factory()->for($user)->create(['title' => 'Latihan peserta']);
        $quiz->complete();
        $other = ExamAttempt::factory()->passingTryout()->create(['title' => 'Tryout orang lain']);
        $other->complete();

        $this->actingAs($user)->get(route('certificates.index'))
            ->assertOk()->assertSee('Tryout peserta')->assertSee('1 sertifikat tersedia')
            ->assertSee(route('certificates.show', $completed))
            ->assertDontSee('Tryout berjalan')->assertDontSee('Latihan peserta')->assertDontSee('Tryout orang lain');
    }

    public function test_empty_list_guides_members_to_start_a_tryout(): void
    {
        $this->actingAs(User::factory()->create())->get(route('certificates.index'))
            ->assertOk()->assertSee('Belum ada sertifikat')->assertSee(route('tryouts.index'))
            ->assertDontSee('Rani Kusuma');
    }

    public function test_completed_tryout_certificate_displays_real_participant_and_scores(): void
    {
        $this->travelTo('2026-10-15 10:00:00');
        $user = User::factory()->create(['name' => 'Dewi Putri']);
        $attempt = ExamAttempt::factory()->passingTryout()->for($user)->create([
            'id' => 184,
            'title' => 'Simulasi SKD Oktober',

        ]);
        $attempt->complete();

        $this->actingAs($user)->get(route('certificates.show', $attempt))
            ->assertOk()->assertSee('Sertifikat Kelulusan Tryout')->assertSee('Dewi Putri')
            ->assertSee('Simulasi SKD Oktober')->assertSee('GN-2026-000184')->assertSee('15 Oktober 2026')
            ->assertSee('Nilai Total')->assertSee('100 <small>/ 100</small>', false)
            ->assertSee('Passing Grade')->assertSee('<strong>60</strong>', false)
            ->assertSee('TWK')->assertSee('TIU')->assertSee('TKP')
            ->assertSee('Cetak / Simpan PDF')->assertDontSee('app-shell', false);
    }

    public function test_another_member_cannot_read_a_certificate(): void
    {
        $attempt = ExamAttempt::factory()->passingTryout()->create();
        $attempt->complete();

        $this->actingAs(User::factory()->create())->get(route('certificates.show', $attempt))->assertNotFound();
    }

    public function test_unfinished_tryout_and_completed_quiz_have_no_certificate(): void
    {
        $user = User::factory()->create();
        $unfinished = ExamAttempt::factory()->passingTryout()->for($user)->create();
        $quiz = ExamAttempt::factory()->for($user)->create();
        $quiz->complete();

        $this->actingAs($user)->get(route('certificates.show', $unfinished))->assertNotFound();
        $this->get(route('certificates.show', $quiz))->assertNotFound();
        $this->get(route('certificates.show', 99999))->assertNotFound();
    }

    #[TestWith(['submitted_at', null])]
    #[TestWith(['score', null])]
    #[TestWith(['max_score', 0])]
    public function test_incomplete_result_data_is_not_printable(string $field, mixed $value): void
    {
        $user = User::factory()->create();
        $attempt = ExamAttempt::factory()->passingTryout()->for($user)->create();
        $attempt->complete();
        $attempt->update([$field => $value]);

        $this->actingAs($user)->get(route('certificates.show', $attempt))->assertNotFound();
        $this->get(route('certificates.index'))->assertSee('Belum ada sertifikat');
    }

    #[TestWith(['inactive'])]
    #[TestWith(['unverified'])]
    public function test_restricted_accounts_cannot_read_certificates(string $state): void
    {
        $user = User::factory()->$state()->create();
        $attempt = ExamAttempt::factory()->passingTryout()->for($user)->create();
        $attempt->complete();

        $this->actingAs($user)->get(route('certificates.show', $attempt))->assertRedirect();
    }

    public function test_certificate_escapes_participant_names_and_tryout_titles(): void
    {
        $name = '<script>alert("name")</script>';
        $title = '<script>alert("title")</script>';
        $user = User::factory()->create(['name' => $name]);
        $attempt = ExamAttempt::factory()->passingTryout()->for($user)->create(['title' => $title]);
        $attempt->complete();

        $this->actingAs($user)->get(route('certificates.show', $attempt))
            ->assertOk()->assertSee($name)->assertSee($title)
            ->assertDontSee($name, false)->assertDontSee($title, false);
    }

    public function test_expired_tryout_is_finalized_and_its_certificate_becomes_available(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        $attempt = ExamAttempt::factory()->passingTryout()->for($user)->create([
            'started_at' => now()->subHour(), 'expires_at' => now()->subMinute(),
        ]);

        $this->actingAs($user)->get(route('certificates.index'))
            ->assertOk()->assertSee(route('certificates.show', $attempt));

        $this->assertSame(ExamAttempt::COMPLETED, $attempt->fresh()->status);
    }

    public function test_result_page_links_to_the_tryout_certificate(): void
    {
        $user = User::factory()->create();
        $attempt = ExamAttempt::factory()->passingTryout()->for($user)->create();
        $attempt->complete();

        $this->actingAs($user)->get($attempt->resultUrl())
            ->assertOk()->assertSee(route('certificates.show', $attempt))->assertSee('Cetak Sertifikat');
    }

    public function test_score_at_the_passing_grade_can_be_printed(): void
    {
        $user = User::factory()->create();
        $attempt = ExamAttempt::factory()->passingTryout()->for($user)->create();
        $attempt->complete();
        $attempt->update(['score' => 60, 'passing_score' => 60]);

        $this->actingAs($user)->get(route('certificates.show', $attempt))
            ->assertOk()->assertSee('60 <small>/ 100</small>', false);
        $this->get($attempt->resultUrl())->assertSee('Cetak Sertifikat');
    }

    #[TestWith([59, 60])]
    #[TestWith([100, null])]
    public function test_failed_tryout_or_missing_passing_grade_has_no_certificate(int $score, ?int $passingScore): void
    {
        $user = User::factory()->create();
        $attempt = ExamAttempt::factory()->passingTryout()->for($user)->create();
        $attempt->complete();
        $attempt->update(['score' => $score, 'passing_score' => $passingScore]);

        $this->actingAs($user)->get(route('certificates.show', $attempt))->assertNotFound();
        $this->get(route('certificates.index'))->assertSee('Belum ada sertifikat');
        $this->get($attempt->resultUrl())->assertDontSee('Cetak Sertifikat');
    }
}
