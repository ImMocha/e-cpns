<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\IntegrationSettings;
use App\Models\User;
use App\Notifications\AccountEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Message;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class IntegrationMailTransportTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function settings(array $overrides = []): array
    {
        return array_replace([
            'version' => 0, 'enabled' => true, 'smtp_host' => 'smtp.gmail.com', 'smtp_port' => 465,
            'smtp_username' => 'sender@example.com', 'smtp_password' => 'first-private-password',
            'from_address' => 'sender@example.com', 'from_name' => 'Ganesha Nusa',
        ], $overrides);
    }

    public function test_email_uses_environment_transport_before_settings_are_saved(): void
    {
        $user = User::factory()->create();

        Mail::raw('Email dengan konfigurasi server.', function (Message $message) use ($user): void {
            $message->to($user->email)->subject('Uji konfigurasi server');
        });

        $email = Mail::mailer('array')->getSymfonyTransport()->messages()->sole()->getOriginalMessage();
        $this->assertSame($user->email, $email->getTo()[0]->getAddress());
    }

    public function test_reused_mailer_reads_new_credentials_and_enforces_tls_on_every_send(): void
    {
        $admin = User::factory()->admin()->create();
        $settings = app(IntegrationSettings::class);
        $settings->update('mail', $this->settings(), $admin, null);
        $transport = new ArrayTransport;
        $configurations = [];
        Mail::extend('smtp', function (array $configuration) use (&$configurations, $transport): ArrayTransport {
            $configurations[] = $configuration;

            return $transport;
        });
        $mailer = Mail::mailer();
        $message = function (Message $message) use ($admin): void {
            $message->to($admin->email)->subject('Uji perubahan konfigurasi');
        };
        $mailer->raw('Pengiriman pertama.', $message);
        $settings->update('mail', $this->settings([
            'version' => 1, 'smtp_password' => 'new-private-password', 'smtp_port' => 587,
            'from_address' => 'new-sender@example.com', 'from_name' => 'Pengirim baru',
        ]), $admin, null);

        $mailer->raw('Pengiriman kedua.', $message);

        $this->assertSame('first-private-password', $configurations[0]['password']);
        $this->assertSame('smtps', $configurations[0]['scheme']);
        $this->assertSame('new-private-password', $configurations[1]['password']);
        $this->assertSame('smtp', $configurations[1]['scheme']);
        $this->assertSame(587, $configurations[1]['port']);
        $this->assertTrue($configurations[1]['require_tls']);
        $this->assertTrue($configurations[1]['verify_peer']);
        $this->assertCount(2, $transport->messages());
        $sent = $transport->messages()->last();
        $this->assertSame('new-sender@example.com', $sent->getOriginalMessage()->getFrom()[0]->getAddress());
        $this->assertSame('new-sender@example.com', $sent->getEnvelope()->getSender()->getAddress());
        $this->assertSame('Pengirim baru', $sent->getOriginalMessage()->getFrom()[0]->getName());
    }

    public function test_queued_notification_uses_credentials_current_at_delivery(): void
    {
        $admin = User::factory()->admin()->create();
        $settings = app(IntegrationSettings::class);
        $settings->update('mail', $this->settings(), $admin, null);
        Queue::fake([SendQueuedNotifications::class]);
        $admin->notify(new AccountEmail('Uji pengiriman', 'Isi email.', 'Buka akun', route('login')));
        $job = unserialize(serialize(Queue::pushed(SendQueuedNotifications::class)->sole()));
        $settings->update('mail', $this->settings(['version' => 1, 'smtp_password' => 'password-after-queue']), $admin, null);
        $transport = new ArrayTransport;
        $configuration = [];
        Mail::extend('smtp', function (array $config) use (&$configuration, $transport): ArrayTransport {
            $configuration = $config;

            return $transport;
        });

        $job->handle(app(ChannelManager::class));

        $this->assertSame('password-after-queue', $configuration['password']);
        $this->assertCount(1, $transport->messages());
        Queue::assertPushed(SendQueuedNotifications::class, 1);
    }

    public function test_disabled_email_does_not_deliver_or_fall_back_to_another_transport(): void
    {
        $admin = User::factory()->admin()->create();
        app(IntegrationSettings::class)->update('mail', $this->settings(['enabled' => false]), $admin, null);
        $transport = new ArrayTransport;
        Mail::extend('smtp', fn (): ArrayTransport => $transport);

        try {
            Mail::raw('Jangan kirim.', function (Message $message) use ($admin): void {
                $message->to($admin->email)->subject('Email dinonaktifkan');
            });
            $this->fail('Email nonaktif harus menolak pengiriman.');
        } catch (TransportException $exception) {
            $this->assertSame('Email belum dapat dikirim. Periksa status dan konfigurasi integrasi email.', $exception->getMessage());
        }

        $this->assertCount(0, $transport->messages());
        $this->assertCount(0, Mail::mailer('array')->getSymfonyTransport()->messages());
    }

    public function test_smtp_errors_do_not_leak_credentials_or_provider_details(): void
    {
        $admin = User::factory()->admin()->create();
        app(IntegrationSettings::class)->update('mail', $this->settings(), $admin, null);
        Mail::extend('smtp', function (): never {
            throw new RuntimeException('first-private-password confidential provider details');
        });

        try {
            Mail::raw('Uji gagal.', function (Message $message) use ($admin): void {
                $message->to($admin->email)->subject('Uji gagal');
            });
            $this->fail('Kesalahan SMTP harus diteruskan dengan pesan aman.');
        } catch (TransportException $exception) {
            $this->assertSame('Email belum dapat dikirim. Periksa status dan konfigurasi integrasi email.', $exception->getMessage());
            $this->assertNull($exception->getPrevious());
        }
    }

    public function test_disabled_email_registration_returns_a_useful_error_instead_of_a_server_error(): void
    {
        $admin = User::factory()->admin()->create();
        app(IntegrationSettings::class)->update('mail', $this->settings(['enabled' => false]), $admin, null);
        $transport = new ArrayTransport;
        Mail::extend('smtp', fn (): ArrayTransport => $transport);

        $this->post(route('register.store'), [
            'name' => 'Peserta Baru', 'email' => 'new-user@example.com',
            'password' => 'password-123', 'password_confirmation' => 'password-123',
            'province' => 'Sulawesi Selatan', 'terms' => '1',
        ])->assertRedirect(route('verification.notice'))->assertSessionHasErrors('email');

        $this->assertDatabaseHas('users', ['email' => 'new-user@example.com', 'email_verified_at' => null]);
        $this->assertCount(0, $transport->messages());
    }
}
