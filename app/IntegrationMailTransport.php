<?php

declare(strict_types=1);

namespace App;

use Illuminate\Mail\MailManager;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;
use Throwable;

class IntegrationMailTransport implements TransportInterface
{
    public function __construct(
        private IntegrationSettings $settings,
        private MailManager $manager,
        private string $fallbackMailer,
    ) {}

    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        try {
            $settings = $this->settings->mail();
            if ($settings === null) {
                if ($this->fallbackMailer === 'integration') {
                    throw new TransportException('Konfigurasi mailer fallback tidak valid.');
                }

                return $this->manager->mailer($this->fallbackMailer)->getSymfonyTransport()->send($message, $envelope);
            }
            if (! $settings['enabled']) {
                throw new TransportException('Pengiriman email sedang dinonaktifkan oleh admin.');
            }
            if (! in_array($settings['smtp_host'], $this->settings->smtpHosts(), true)) {
                throw new TransportException('Server SMTP tidak diizinkan.');
            }

            $transport = $this->manager->createSymfonyTransport([
                'transport' => 'smtp', 'scheme' => $settings['smtp_port'] === 465 ? 'smtps' : 'smtp',
                'host' => $settings['smtp_host'], 'port' => $settings['smtp_port'],
                'username' => $settings['smtp_username'], 'password' => $settings['smtp_password'],
                'timeout' => 15, 'require_tls' => true, 'verify_peer' => true,
            ]);
            $sender = new Address($settings['from_address'], $settings['from_name']);
            $message = clone $message;
            if ($message instanceof Email) {
                $message->from($sender);
            }
            $envelope = new Envelope($sender, ($envelope ?? Envelope::create($message))->getRecipients());

            return $transport->send($message, $envelope);
        } catch (Throwable) {
            throw new TransportException('Email belum dapat dikirim. Periksa status dan konfigurasi integrasi email.');
        }
    }

    public function __toString(): string
    {
        return 'integration-mail';
    }
}
