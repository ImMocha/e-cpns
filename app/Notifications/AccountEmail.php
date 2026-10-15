<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    /** @param array<string, string> $details */
    public function __construct(
        public string $headline,
        public string $message,
        public string $actionText,
        public string $actionUrl,
        public ?string $preference = null,
        public array $details = [],
    ) {
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->shouldSend($notifiable, 'mail') ? ['mail'] : [];
    }

    public function shouldSend(User $notifiable, string $channel): bool
    {
        return $channel === 'mail' && ! $notifiable->trashed()
            && ($this->preference === null || $notifiable->wantsEmailNotification($this->preference));
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->headline.' — '.config('app.name'))
            ->view(['html' => 'emails.notification', 'text' => 'emails.notification-text'], [
                'headline' => $this->headline,
                'intro' => $this->message,
                'actionText' => $this->actionText,
                'actionUrl' => $this->actionUrl,
                'details' => $this->details,
                'outro' => $this->preference === null
                    ? 'Jika kamu tidak melakukan perubahan ini, segera hubungi admin dan amankan akunmu.'
                    : 'Kamu menerima email ini sesuai preferensi notifikasi akunmu.',
                'preferencesUrl' => $this->preference === null ? null : route('profile.edit'),
            ]);
    }
}
