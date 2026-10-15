<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\Province;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Mockery;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{name: string, email: string, password: string, password_confirmation: string, terms: string, province: string} */
    private function registration(): array
    {
        return [
            'name' => 'Peserta Baru', 'email' => 'peserta@example.com',
            'password' => 'password-123', 'password_confirmation' => 'password-123',
            'terms' => '1', 'province' => Province::cases()[0]->value,
        ];
    }

    public function test_registration_sends_verification_email_and_signs_in_the_unverified_user(): void
    {
        Notification::fake();

        $this->post(route('register.store'), $this->registration())->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'peserta@example.com')->sole();
        $this->assertAuthenticatedAs($user);
        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_registration_preserves_the_account_and_session_if_smtp_is_unavailable(): void
    {
        Exceptions::fake();
        $transport = Mockery::mock(TransportInterface::class);
        $transport->shouldReceive('send')->once()->andThrow(new TransportException('SMTP unavailable'));
        Mail::mailer()->setSymfonyTransport($transport);

        $this->post(route('register.store'), $this->registration())
            ->assertRedirect(route('verification.notice'))->assertSessionHasErrors('email');

        $user = User::where('email', 'peserta@example.com')->sole();
        $this->assertAuthenticatedAs($user);
        $this->assertFalse($user->hasVerifiedEmail());
        Exceptions::assertReported(TransportException::class);
    }

    public function test_unverified_user_can_resend_the_verification_email(): void
    {
        $user = User::factory()->unverified()->create();
        Notification::fake();

        $this->actingAs($user)->from(route('verification.notice'))
            ->post(route('verification.send'))->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status', 'verification-link-sent');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_signed_verification_link_verifies_the_correct_user(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id, 'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)->get($url)->assertRedirect(route('dashboard'));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_verification_rejects_tampered_and_expired_links(): void
    {
        $this->freezeTime();
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinute(), [
            'id' => $user->id, 'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)->get(str_replace(sha1($user->email), str_repeat('a', 40), $url))->assertForbidden();
        $this->travel(2)->minutes();
        $this->get($url)->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_verification_link_cannot_be_used_for_another_account(): void
    {
        $owner = User::factory()->unverified()->create();
        $other = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $owner->id, 'hash' => sha1($owner->email),
        ]);

        $this->actingAs($other)->get($url)->assertForbidden();

        $this->assertFalse($owner->fresh()->hasVerifiedEmail());
        $this->assertFalse($other->fresh()->hasVerifiedEmail());
    }

    public function test_verified_users_do_not_receive_another_verification_email(): void
    {
        $user = User::factory()->create();
        Notification::fake();

        $this->actingAs($user)->get(route('verification.notice'))->assertRedirect(route('dashboard'));
        $this->post(route('verification.send'))->assertRedirect(route('dashboard'));

        Notification::assertNothingSent();
    }

    public function test_verification_resend_is_rate_limited(): void
    {
        $user = User::factory()->unverified()->create();
        Notification::fake();
        $this->actingAs($user);

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->post(route('verification.send'))->assertSessionHasNoErrors();
        }
        $this->post(route('verification.send'))->assertTooManyRequests();

        Notification::assertSentToTimes($user, VerifyEmail::class, 6);
    }

    public function test_verification_email_renders_html_and_plain_text_with_the_signed_link(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->post(route('verification.send'))->assertSessionHasNoErrors();

        $email = Mail::mailer('array')->getSymfonyTransport()->messages()->sole()->getOriginalMessage();
        $this->assertStringContainsString('Verifikasi email kamu', $email->getHtmlBody());
        $this->assertStringContainsString('/email/verify/'.$user->id.'/'.sha1($user->email), $email->getTextBody());
        $this->assertStringContainsString('&signature=', $email->getTextBody());
    }

    public function test_verification_resend_reports_smtp_failure_without_claiming_success(): void
    {
        $user = User::factory()->unverified()->create();
        Exceptions::fake();
        $transport = Mockery::mock(TransportInterface::class);
        $transport->shouldReceive('send')->once()->andThrow(new TransportException('SMTP unavailable'));
        Mail::mailer()->setSymfonyTransport($transport);

        $this->actingAs($user)->post(route('verification.send'))
            ->assertSessionHasErrors('email')->assertSessionMissing('status');

        Exceptions::assertReported(TransportException::class);
    }

    public function test_guests_and_inactive_users_cannot_request_verification_emails(): void
    {
        $user = User::factory()->inactive()->unverified()->create();
        Notification::fake();

        $this->post(route('verification.send'))->assertRedirect(route('login'));
        $this->actingAs($user)->post(route('verification.send'))->assertRedirect(route('login'));

        Notification::assertNothingSent();
        $this->assertGuest();
    }

    public function test_unverified_users_are_redirected_to_verification_before_accessing_member_pages(): void
    {
        $this->actingAs(User::factory()->unverified()->create())
            ->get(route('dashboard'))->assertRedirect(route('verification.notice'));
    }
}
