<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Emails the short-lived PIN the user types to reset their password.
 *
 * Queued on purpose: the request must not block on the mail provider. That also
 * keeps the response time identical whether or not the address is registered,
 * so the endpoint cannot be used to enumerate accounts.
 */
class PasswordResetPinNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The PIN expires in minutes, so a slow retry is worse than none at all.
     */
    public int $tries = 3;

    public function __construct(
        public string $pin,
        public int $minutes,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
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
