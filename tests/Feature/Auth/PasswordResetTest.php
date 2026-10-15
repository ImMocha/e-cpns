<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\AccountEmail;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_open_recovery_forms_from_login(): void
    {
        $this->get(route('login'))->assertSee(route('password.request'));
        $this->get(route('password.request'))->assertSee('Kirim tautan reset password');
        $this->get(route('password.reset', ['token' => 'sample-token', 'email' => 'peserta@example.com']))
            ->assertSee('Buat password baru')->assertSee('peserta@example.com')->assertSee('sample-token');
    }

    public function test_active_user_receives_a_reset_link_using_the_configured_website_address(): void
    {
        $user = User::factory()->unverified()->create();
        Notification::fake();

        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHasNoErrors()->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $url = $notification->toMail($user)->viewData['actionUrl'];

            return $url === rtrim(config('app.url'), '/').'/reset-password/'.$notification->token.'?email='.urlencode($user->email)
                && Password::tokenExists($user, $notification->token);
        });
    }

    public function test_unknown_inactive_and_deleted_accounts_receive_the_same_response_without_email(): void
    {
        $inactive = User::factory()->inactive()->create();
        $deleted = User::factory()->create();
        $deleted->delete();
        Notification::fake();

        foreach (['unknown@example.com', $inactive->email, $deleted->email] as $email) {
            $this->post(route('password.email'), ['email' => $email])
                ->assertSessionHasNoErrors()
                ->assertSessionHas('status', 'Jika email terdaftar pada akun aktif, tautan reset password akan dikirim. Periksa juga folder spam.');
        }

        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_reset_link_requests_are_throttled_without_sending_duplicate_emails(): void
    {
        $user = User::factory()->create();
        Notification::fake();

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->post(route('password.email'), ['email' => $user->email])->assertSessionHasNoErrors();
        }
        $this->post(route('password.email'), ['email' => $user->email])->assertTooManyRequests();

        Notification::assertSentToTimes($user, ResetPassword::class, 1);
    }

    public function test_reset_requests_validate_email(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'invalid'])->assertSessionHasErrors('email');

        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_valid_token_changes_password_rotates_remember_token_and_sends_security_email(): void
    {
        $user = User::factory()->create();
        $rememberToken = $user->remember_token;
        $token = Password::createToken($user);
        Notification::fake();
        Event::fake([PasswordReset::class]);

        $this->post(route('password.update'), [
            'email' => $user->email, 'token' => $token,
            'password' => 'new-password-123', 'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('login'))->assertSessionHasNoErrors()->assertSessionHas('status');

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->assertNotSame($rememberToken, $user->fresh()->remember_token);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        $this->assertGuest();
        Event::assertDispatched(PasswordReset::class, fn (PasswordReset $event): bool => $event->user->is($user));
        Notification::assertSentTo($user, AccountEmail::class, fn (AccountEmail $notification): bool => $notification->preference === null);

        $this->post(route('password.update'), [
            'email' => $user->email, 'token' => $token,
            'password' => 'another-password', 'password_confirmation' => 'another-password',
        ])->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }

    public function test_expired_token_does_not_change_password(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        $oldPassword = $user->password;
        $token = Password::createToken($user);
        $this->travel(61)->minutes();
        Notification::fake();

        $this->post(route('password.update'), [
            'email' => $user->email, 'token' => $token,
            'password' => 'new-password-123', 'password_confirmation' => 'new-password-123',
        ])->assertSessionHasErrors('email');

        $this->assertSame($oldPassword, $user->fresh()->password);
        Notification::assertNothingSent();
    }

    public function test_token_cannot_reset_another_users_password_or_an_inactive_account(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $oldPassword = $other->password;
        $token = Password::createToken($user);
        Notification::fake();

        $this->post(route('password.update'), [
            'email' => $other->email, 'token' => $token,
            'password' => 'new-password-123', 'password_confirmation' => 'new-password-123',
        ])->assertSessionHasErrors('email');
        $user->update(['status' => 'inactive']);
        $this->post(route('password.update'), [
            'email' => $user->email, 'token' => $token,
            'password' => 'new-password-123', 'password_confirmation' => 'new-password-123',
        ])->assertSessionHasErrors('email');

        $this->assertSame($oldPassword, $other->fresh()->password);
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
        Notification::assertNothingSent();
    }

    /** @return array<string, array{string, string}> */
    public static function invalidPasswords(): array
    {
        return ['too short' => ['short', 'short'], 'confirmation mismatch' => ['new-password', 'different-password']];
    }

    #[DataProvider('invalidPasswords')]
    public function test_reset_rejects_invalid_password_without_consuming_token(string $password, string $confirmation): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        Notification::fake();

        $this->post(route('password.update'), [
            'email' => $user->email, 'token' => $token,
            'password' => $password, 'password_confirmation' => $confirmation,
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
        $this->assertTrue(Password::tokenExists($user, $token));
        Notification::assertNothingSent();
    }

    public function test_smtp_failure_reports_a_useful_error_and_allows_an_immediate_retry(): void
    {
        $user = User::factory()->create();
        Exceptions::fake();
        $transport = Mockery::mock(TransportInterface::class);
        $transport->shouldReceive('send')->once()->andThrow(new TransportException('SMTP unavailable'));
        Mail::mailer()->setSymfonyTransport($transport);

        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHasErrors([
            'email' => 'Email belum dapat dikirim. Silakan coba lagi beberapa saat atau hubungi admin.',
        ]);

        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        Exceptions::assertReported(TransportException::class);
    }

    public function test_reset_email_renders_html_and_plain_text_with_a_working_link(): void
    {
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHasNoErrors();

        $email = Mail::mailer('array')->getSymfonyTransport()->messages()->sole()->getOriginalMessage();
        $this->assertSame($user->email, $email->getTo()[0]->getAddress());
        $this->assertStringContainsString('Reset password akunmu', $email->getHtmlBody());
        $this->assertStringContainsString('/reset-password/', $email->getTextBody());
        $this->assertStringContainsString('?email='.urlencode($user->email), $email->getTextBody());
    }

    public function test_authenticated_users_are_redirected_away_from_password_recovery(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('password.request'))->assertRedirect(route('dashboard'));
    }
}
