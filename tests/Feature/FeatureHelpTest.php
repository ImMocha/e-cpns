<?php

namespace Tests\Feature;

use App\Enums\ExamBank;
use App\Models\ExamAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureHelpTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_gets_admin_guides_and_destinations_on_the_current_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.exams.create'))
            ->assertOk()->assertSee('data-help-audience="admin"', false)
            ->assertSee('data-help-route="admin.exams.create"', false)
            ->assertSee('Bantuan &amp; Panduan', false)
            ->assertSee('data-help-destination="admin-packages"', false)
            ->assertSee('Bantuan editor soal')
            ->assertDontSee('data-help-destination="member-profile"', false);
    }

    public function test_member_help_does_not_expose_admin_navigation(): void
    {
        $this->actingAs(User::factory()->create())->get(route('dashboard'))
            ->assertOk()->assertSee('data-help-audience="member"', false)
            ->assertSee('data-help-destination="member-certificates"', false)
            ->assertDontSee('data-help-destination="admin-users"', false);
    }

    public function test_admin_in_member_mode_gets_member_help(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get(route('dashboard'))
            ->assertOk()->assertSee('data-help-audience="member"', false)
            ->assertDontSee('data-help-audience="admin"', false);
    }

    public function test_guests_are_not_shown_authenticated_help_navigation(): void
    {
        $this->get(route('login'))->assertOk()->assertDontSee('data-feature-help', false);
    }

    public function test_help_is_available_during_an_exam_without_changing_the_attempt(): void
    {
        $user = User::factory()->create();
        $attempt = ExamAttempt::resumeOrStart($user, ExamBank::TryoutSkd5);

        $this->actingAs($user)->get($attempt->attemptUrl())
            ->assertOk()->assertSee('data-help-route="tryouts.attempt"', false)
            ->assertSee('timer tetap berjalan saat bantuan terbuka');

        $this->assertSame(ExamAttempt::IN_PROGRESS, $attempt->fresh()->status);
        $this->assertSame([], $attempt->fresh()->answers);
    }

    public function test_printable_certificate_keeps_help_out_of_printed_content(): void
    {
        $user = User::factory()->create();
        $attempt = ExamAttempt::factory()->passingTryout()->for($user)->create();
        $attempt->complete();

        $this->actingAs($user)->get(route('certificates.show', $attempt))
            ->assertOk()->assertSee('class="feature-help no-print"', false)
            ->assertSee('data-help-route="certificates.show"', false);
    }
}
