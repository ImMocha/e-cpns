<?php

namespace App\Providers;

use App\IntegrationMailTransport;
use App\IntegrationSettings;
use App\MidtransGateway;
use App\Models\Article;
use App\Models\Exam;
use App\Models\Material;
use App\Models\Package;
use App\Models\Transaction;
use App\Models\User;
use App\Observers\AccountEmailObserver;
use App\Observers\ContentEmailObserver;
use App\Observers\PaymentEmailObserver;
use App\PaymentGatewayContract;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Mail\MailManager;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGatewayContract::class, MidtransGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $fallbackMailer = config('mail.integration_fallback', config('mail.default'));
        config([
            'mail.integration_fallback' => $fallbackMailer,
            'mail.mailers.integration' => ['transport' => 'integration'], 'mail.default' => 'integration',
        ]);
        Mail::extend('integration', fn () => new IntegrationMailTransport(
            $this->app->make(IntegrationSettings::class), $this->app->make(MailManager::class), $fallbackMailer,
        ));
        RateLimiter::for('integration-settings', fn (Request $request): Limit => Limit::perMinute(6)->by((string) $request->user()->id));
        RateLimiter::for('integration-connection-tests', fn (Request $request): Limit => Limit::perMinute(3)->by((string) $request->user()->id));

        User::observe(AccountEmailObserver::class);
        Transaction::observe(PaymentEmailObserver::class);
        foreach ([Exam::class, Material::class, Package::class, Article::class] as $model) {
            $model::observe(ContentEmailObserver::class);
        }

        ResetPassword::toMailUsing(function (User $user, string $token): MailMessage {
            $url = rtrim(config('app.url'), '/').route('password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()], false);

            return (new MailMessage)->subject('Reset password — '.config('app.name'))
                ->view(['html' => 'emails.notification', 'text' => 'emails.notification-text'], [
                    'headline' => 'Reset password akunmu',
                    'intro' => 'Kami menerima permintaan untuk mereset password akun Ganesha Nusa kamu.',
                    'actionText' => 'Reset password', 'actionUrl' => $url,
                    'details' => ['Berlaku selama' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire').' menit'],
                    'outro' => 'Jika kamu tidak meminta reset password, abaikan email ini. Password kamu belum berubah.',
                    'preferencesUrl' => null,
                ]);
        });

        VerifyEmail::toMailUsing(fn (User $user, string $url): MailMessage => (new MailMessage)
            ->subject('Verifikasi email — '.config('app.name'))
            ->view(['html' => 'emails.notification', 'text' => 'emails.notification-text'], [
                'headline' => 'Verifikasi email kamu',
                'intro' => 'Selamat datang di Ganesha Nusa. Verifikasi alamat email ini untuk mengaktifkan akses belajar dan tryout.',
                'actionText' => 'Verifikasi email', 'actionUrl' => $url,
                'details' => ['Berlaku selama' => config('auth.verification.expire', 60).' menit'],
                'outro' => 'Jika kamu tidak membuat akun ini, abaikan email ini.',
                'preferencesUrl' => null,
            ]));
    }
}
