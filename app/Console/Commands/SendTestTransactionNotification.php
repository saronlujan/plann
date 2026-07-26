<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Models\User;
use App\Notifications\TransactionDueNotification;
use App\Support\Tenancy\TenantContext;
use App\Support\Transactions\TransactionProjector;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Dev helper: fire a "due today" reminder immediately (synchronously, bypassing
 * the enabled/dedup checks) for a transaction dated today, so email delivery can
 * be verified on demand.
 */
class SendTestTransactionNotification extends Command
{
    protected $signature = 'app:send-test-notification {email? : Target user email (defaults to the first user)}';

    protected $description = 'Send a test due-today notification to a user right now.';

    public function handle(TransactionProjector $projector, TenantContext $context): int
    {
        $user = $this->argument('email')
            ? User::query()->where('email', $this->argument('email'))->first()
            : User::query()->first();

        if ($user === null) {
            $this->error('No user found.');

            return self::FAILURE;
        }

        $context->setTenantId($user->tenant_id);

        $today = CarbonImmutable::now()->startOfDay();
        $transactions = Transaction::query()->with(['currency', 'account'])->get();

        $items = $this->itemsDueToday($projector, $transactions, $today);

        if ($items === []) {
            $items = $this->fallbackItem($transactions, $today);
        }

        if ($items === []) {
            $this->warn('No transactions found for this tenant. Create one first.');

            return self::FAILURE;
        }

        Notification::sendNow($user, new TransactionDueNotification('due_today', $items));

        $context->clear();

        $this->info(sprintf('Sent test notification to %s about %d transaction(s).', $user->email, count($items)));

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, Transaction>  $transactions
     * @return array<int, array{description: string, amount: string, date: string, account: string}>
     */
    private function itemsDueToday(TransactionProjector $projector, $transactions, CarbonImmutable $today): array
    {
        return $projector->entriesForPeriod($transactions, $today->startOfMonth())
            ->filter(fn (array $entry): bool => $entry['date'] === $today->toDateString())
            ->map(fn (array $entry): array => [
                'description' => $entry['description'],
                'amount' => $entry['currency_symbol'].' '.number_format((float) $entry['amount'], 2, ',', '.'),
                'date' => $today->format('d/m/Y'),
                'account' => $entry['source'],
            ])
            ->values()
            ->all();
    }

    /**
     * When nothing is due today, present the most recent transaction as if it
     * were due today so the email still goes out for testing.
     *
     * @param  Collection<int, Transaction>  $transactions
     * @return array<int, array{description: string, amount: string, date: string, account: string}>
     */
    private function fallbackItem($transactions, CarbonImmutable $today): array
    {
        $transaction = $transactions->sortByDesc('id')->first();

        if ($transaction === null) {
            return [];
        }

        return [[
            'description' => $transaction->description,
            'amount' => $transaction->currency->symbol.' '.number_format((float) $transaction->amount, 2, ',', '.'),
            'date' => $today->format('d/m/Y'),
            'account' => $transaction->account->name ?? 'Sem origem',
        ]];
    }
}
