<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionNotification;
use App\Models\User;
use App\Notifications\TransactionDueNotification;
use App\Support\Tenancy\TenantContext;
use App\Support\Transactions\TransactionProjector;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class SendDueTransactionNotifications extends Command
{
    protected $signature = 'app:send-due-transaction-notifications';

    protected $description = 'Notify users about transactions that are due today or coming due (queued).';

    public function handle(TransactionProjector $projector, TenantContext $context): int
    {
        $today = CarbonImmutable::now()->startOfDay();
        $sent = 0;

        Tenant::query()->each(function (Tenant $tenant) use ($projector, $context, $today, &$sent): void {
            $context->setTenantId($tenant->id);

            $users = User::query()
                ->where('tenant_id', $tenant->id)
                ->where('notifications_enabled', true)
                ->get();

            if ($users->isEmpty()) {
                return;
            }

            $transactions = Transaction::query()->with(['currency', 'account'])->get();

            if ($transactions->isEmpty()) {
                return;
            }

            // Every date we might need across users: today (due-today) plus each
            // distinct "X days before" horizon. Entries are projected per month.
            $targetDates = collect([$today])
                ->merge($users->map(fn (User $user): CarbonImmutable => $today->addDays(max(0, $user->notify_days_before))))
                ->unique(fn (CarbonImmutable $date): string => $date->toDateString());

            $entriesByDate = $this->entriesByDate($projector, $transactions, $targetDates);

            foreach ($users as $user) {
                $sent += $this->notifyUser($user, $today, $entriesByDate);
            }
        });

        $context->clear();

        $this->info("Dispatched notifications for {$sent} user(s).");

        return self::SUCCESS;
    }

    /**
     * Project each target date's month once and index the unpaid entries by date.
     *
     * @param  Collection<int, Transaction>  $transactions
     * @param  Collection<int, CarbonImmutable>  $targetDates
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function entriesByDate(TransactionProjector $projector, Collection $transactions, Collection $targetDates): array
    {
        $byMonth = [];

        foreach ($targetDates as $date) {
            $monthKey = $date->format('Y-m');

            if (! isset($byMonth[$monthKey])) {
                $byMonth[$monthKey] = $projector->entriesForPeriod($transactions, $date->startOfMonth());
            }
        }

        $result = [];

        foreach ($targetDates as $date) {
            $dateKey = $date->toDateString();
            $result[$dateKey] = $byMonth[$date->format('Y-m')]
                ->filter(fn (array $entry): bool => $entry['date'] === $dateKey && $entry['paid_at'] === null)
                ->values()
                ->all();
        }

        return $result;
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $entriesByDate
     */
    private function notifyUser(User $user, CarbonImmutable $today, array $entriesByDate): int
    {
        $upcomingDate = $today->addDays(max(0, $user->notify_days_before));

        $buckets = [
            'due_today' => $entriesByDate[$today->toDateString()] ?? [],
            'upcoming' => $entriesByDate[$upcomingDate->toDateString()] ?? [],
        ];

        $dispatched = 0;

        foreach ($buckets as $kind => $entries) {
            $fresh = $this->freshEntries($user, $kind, $entries);

            if ($fresh === []) {
                continue;
            }

            Notification::send($user, new TransactionDueNotification($kind, array_map($this->toItem(...), $fresh)));

            foreach ($fresh as $entry) {
                TransactionNotification::query()->create([
                    'user_id' => $user->id,
                    'entry_key' => $entry['id'],
                    'kind' => $kind,
                    'due_date' => $entry['date'],
                ]);
            }

            $dispatched++;
        }

        return $dispatched;
    }

    /**
     * Drop entries already notified for this user/kind/occurrence.
     *
     * @param  array<int, array<string, mixed>>  $entries
     * @return array<int, array<string, mixed>>
     */
    private function freshEntries(User $user, string $kind, array $entries): array
    {
        return array_values(array_filter($entries, fn (array $entry): bool => ! TransactionNotification::query()
            ->where('user_id', $user->id)
            ->where('entry_key', $entry['id'])
            ->where('kind', $kind)
            ->whereDate('due_date', $entry['date'])
            ->exists()));
    }

    /**
     * @param  array<string, mixed>  $entry
     * @return array{description: string, amount: string, date: string, account: string}
     */
    private function toItem(array $entry): array
    {
        return [
            'description' => $entry['description'],
            'amount' => $entry['currency_symbol'].' '.number_format((float) $entry['amount'], 2, ',', '.'),
            'date' => CarbonImmutable::parse($entry['date'])->format('d/m/Y'),
            'account' => $entry['source'],
        ];
    }
}
