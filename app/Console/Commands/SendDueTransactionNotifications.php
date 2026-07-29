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

    protected $description = 'Notify users about overdue transactions and those due today or coming due (queued).';

    /**
     * How far back the overdue sweep looks. Each occurrence is only ever
     * reported once (see TransactionNotification), so a wider window would only
     * add projection cost, not extra reminders.
     */
    private const OVERDUE_LOOKBACK_DAYS = 60;

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

            $transactions = Transaction::query()->with(TransactionProjector::RELATIONS)->get();

            if ($transactions->isEmpty()) {
                return;
            }

            // Every date we might need across users: today (due-today) plus each
            // distinct "X days before" horizon. Entries are projected per month.
            $targetDates = collect([$today])
                ->merge($users->map(fn (User $user): CarbonImmutable => $today->addDays(max(0, $user->notify_days_before))))
                ->unique(fn (CarbonImmutable $date): string => $date->toDateString());

            $entriesByDate = $this->entriesByDate($projector, $transactions, $targetDates);
            $overdue = $this->overdueEntries($projector, $transactions, $today);

            foreach ($users as $user) {
                $sent += $this->notifyUser($user, $today, $entriesByDate, $overdue);
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
     * Unpaid entries whose due date has already passed, within the lookback
     * window. Bounded on purpose: without it every run would re-project the
     * tenant's entire history month by month.
     *
     * @param  Collection<int, Transaction>  $transactions
     * @return array<int, array<string, mixed>>
     */
    private function overdueEntries(TransactionProjector $projector, Collection $transactions, CarbonImmutable $today): array
    {
        $windowStart = $today->subDays(self::OVERDUE_LOOKBACK_DAYS);
        $todayKey = $today->toDateString();
        $windowStartKey = $windowStart->toDateString();

        $entries = [];
        $period = $windowStart->startOfMonth();
        $lastPeriod = $today->startOfMonth();

        while ($period->lessThanOrEqualTo($lastPeriod)) {
            foreach ($projector->entriesForPeriod($transactions, $period) as $entry) {
                if ($entry['paid_at'] === null && $entry['date'] >= $windowStartKey && $entry['date'] < $todayKey) {
                    $entries[] = $entry;
                }
            }

            $period = $period->addMonth();
        }

        usort($entries, fn (array $a, array $b): int => $a['date'] <=> $b['date']);

        return $entries;
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $entriesByDate
     * @param  array<int, array<string, mixed>>  $overdue
     */
    private function notifyUser(User $user, CarbonImmutable $today, array $entriesByDate, array $overdue): int
    {
        $upcomingDate = $today->addDays(max(0, $user->notify_days_before));

        $buckets = [
            'overdue' => $overdue,
            'due_today' => $entriesByDate[$today->toDateString()] ?? [],
        ];

        // With notify_days_before = 0 (allowed by the preferences form) the
        // "upcoming" horizon lands on today, which would mail the same entries
        // a second time under a different kind.
        if (! $upcomingDate->isSameDay($today)) {
            $buckets['upcoming'] = $entriesByDate[$upcomingDate->toDateString()] ?? [];
        }

        $dispatched = 0;

        foreach ($buckets as $kind => $entries) {
            $fresh = $this->freshEntries($user, $kind, $entries);

            if ($fresh === []) {
                continue;
            }

            // Queued notification: without an explicit locale the worker would
            // render every email in the app default language.
            Notification::send(
                $user,
                (new TransactionDueNotification($kind, array_map($this->toItem(...), $fresh)))
                    ->locale($user->locale ?: config('app.locale')),
            );

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
