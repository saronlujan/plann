<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Emails the short-lived PIN the user types to reset their password. Sent
 * synchronously (not queued) so the code arrives immediately.
 */
class PasswordResetPinNotification extends Notification
{
    public function __construct(
        public string $pin,
        public int $minutes,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('auth.ui.reset.email_subject'))
            ->greeting(__('auth.ui.reset.email_greeting', ['name' => $notifiable->name]))
            ->line(__('auth.ui.reset.email_intro'))
            ->line('# '.$this->pin)
            ->line(__('auth.ui.reset.email_expires', ['minutes' => $this->minutes]))
            ->line(__('auth.ui.reset.email_ignore'));
    }
}
