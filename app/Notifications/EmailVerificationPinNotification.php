<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Emails the PIN a new user types to confirm they own the address.
 *
 * Queued so signup never blocks on the mail provider.
 */
class EmailVerificationPinNotification extends Notification implements ShouldQueue
{
    use Queueable;

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
            ->subject(__('auth.ui.verify.email_subject'))
            ->greeting(__('auth.ui.verify.email_greeting', ['name' => $notifiable->name]))
            ->line(__('auth.ui.verify.email_intro'))
            ->line('# '.$this->pin)
            ->line(__('auth.ui.verify.email_expires', ['minutes' => $this->minutes]))
            ->line(__('auth.ui.verify.email_ignore'));
    }
}
