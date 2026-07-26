<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Reminder about transactions that are overdue, due today or coming due.
 * Delivered on the queue by email (through Resend).
 */
class TransactionDueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  'overdue'|'due_today'|'upcoming'  $kind
     * @param  array<int, array{description: string, amount: string, date: string, account: string}>  $items
     */
    public function __construct(
        public string $kind,
        public array $items,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return filled($notifiable->email) ? ['mail'] : [];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject(__("notifications.transactions_due.subject.{$this->kind}"))
            ->greeting(__('notifications.transactions_due.greeting', ['name' => $notifiable->name]))
            ->line(__("notifications.transactions_due.intro.{$this->kind}"));

        // Overdue items read in the past tense; everything else is still ahead.
        $itemKey = $this->kind === 'overdue'
            ? 'notifications.transactions_due.item_overdue'
            : 'notifications.transactions_due.item';

        foreach ($this->items as $item) {
            $mail->line('• '.__($itemKey, [
                'description' => $item['description'],
                'amount' => $item['amount'],
                'date' => $item['date'],
                'account' => $item['account'],
            ]));
        }

        return $mail->line(__('notifications.transactions_due.footer'));
    }
}
