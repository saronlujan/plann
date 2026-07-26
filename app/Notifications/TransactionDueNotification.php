<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Reminder about transactions that are due today or coming due. Delivered on the
 * queue by email (through Resend).
 */
class TransactionDueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  'due_today'|'upcoming'  $kind
     * @param  array<int, array{description: string, amount: string, date: string, account: string}>  $items
     */
    public function __construct(
        public string $kind,
        public array $items,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return filled($notifiable->email) ? ['mail'] : [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->subject())
            ->greeting('Olá, '.$notifiable->name.'!')
            ->line($this->intro());

        foreach ($this->items as $item) {
            $mail->line(sprintf('• %s — %s (vence em %s, %s)', $item['description'], $item['amount'], $item['date'], $item['account']));
        }

        return $mail->line('Acesse o plann.money para acompanhar suas transações.');
    }

    private function subject(): string
    {
        return $this->kind === 'due_today'
            ? 'Transações que vencem hoje'
            : 'Transações a vencer';
    }

    private function intro(): string
    {
        return $this->kind === 'due_today'
            ? 'Você tem transações que vencem hoje:'
            : 'Você tem transações que vão vencer em breve:';
    }
}
