<?php

namespace App\Http\Controllers\Transaction;

use App\Enums\TransactionInstallmentFrequency;
use App\Enums\TransactionMovementType;
use App\Enums\TransactionPeriodScale;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\IndexTransactionsRequest;
use App\Models\Account;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Currency;
use App\Models\Service;
use App\Models\Tag;
use App\Models\Transaction;
use App\Support\Transactions\TransactionProjector;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class IndexTransactionController extends Controller
{
    public function __invoke(IndexTransactionsRequest $request, TransactionProjector $projector): Response
    {
        $tenant = $request->user()?->tenant()->with('activeCurrencies')->first();
        abort_if($tenant === null, 403);

        $activeCurrencies = $tenant->activeCurrencies()
            ->orderBy('code')
            ->get(['currencies.id', 'currencies.code', 'currencies.name', 'currencies.symbol']);
        $activeCurrencyIds = $activeCurrencies->pluck('id')->all();

        [$from, $to] = $request->range();

        // Bounded with the time attached, not just the day: SQLite keeps the
        // column as "2026-08-15 00:00:00" and compares it as text, so a bare
        // "<= 2026-08-15" sorts after the row and drops the very day asked for.
        // Harmless while the bound was always a month end; not once it can be
        // the same day as an entry.
        $windowStart = $from->startOfDay()->toDateTimeString();
        $windowEnd = $to->endOfDay()->toDateTimeString();

        // A recurrence or an instalment that began before the window still has
        // occurrences inside it, so only one-offs can be excluded by their date.
        $transactions = Transaction::query()
            ->with(TransactionProjector::RELATIONS)
            ->where('effective_date', '<=', $windowEnd)
            ->where(function (Builder $query) use ($windowStart): void {
                $query->where('type', '!=', 'unique')
                    ->orWhere('effective_date', '>=', $windowStart);
            })
            ->orderBy('effective_date')
            ->orderBy('id')
            ->get();

        $transferLegs = $this->transferLegs($transactions);

        $entries = $this->sort(
            $this->filter(
                $projector->entriesForRange($transactions, $from, $to)
                    ->map(fn (array $entry): array => $this->withTransferLegs($entry, $transferLegs)),
                $request,
            ),
            $request->order(),
        );

        // Totalled from what is on screen, filters included: a summary that
        // described a wider set than the list under it would just be confusing.
        $summaries = $projector->summaries($activeCurrencies, $entries);

        return Inertia::render('Transactions/Index', [
            // What is on screen, so the summary drawer can name what it totals and
            // the period bar knows where the arrows step from.
            'period' => $from->format('Y-m'),
            'filters' => [
                'scale' => $request->scale()->value,
                'date' => $request->anchor()->toDateString(),
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'custom_range' => $request->hasExplicitRange(),
                'search' => $request->search(),
                'movement' => $request->movement()->value ?? '',
                'status' => $request->status() ?? '',
                'order' => $request->order(),
            ],
            'scaleOptions' => TransactionPeriodScale::options(),
            'movementTypeOptions' => array_map(
                fn (TransactionMovementType $type): array => ['value' => $type->value, 'label' => $type->label()],
                TransactionMovementType::cases(),
            ),
            'scheduleTypeOptions' => array_map(
                fn (TransactionType $type): array => ['value' => $type->value, 'label' => $type->label()],
                TransactionType::cases(),
            ),
            'frequencyOptions' => array_map(
                fn (TransactionInstallmentFrequency $frequency): array => ['value' => $frequency->value, 'label' => $frequency->label()],
                TransactionInstallmentFrequency::cases(),
            ),
            'currencyOptions' => $activeCurrencies
                ->map(fn (Currency $currency): array => [
                    'id' => $currency->id,
                    'code' => $currency->code,
                    'name' => $currency->name,
                    'symbol' => $currency->symbol,
                ])
                ->values()
                ->all(),
            'accountOptions' => Account::query()
                ->where('tenant_id', $tenant->id)
                ->whereIn('currency_id', $activeCurrencyIds)
                ->orderBy('name')
                ->get(['id', 'name', 'currency_id'])
                ->map(fn (Account $account): array => [
                    'id' => $account->id,
                    'name' => $account->name,
                    'currency_id' => $account->currency_id,
                ])
                ->values()
                ->all(),
            'categoryOptions' => Category::query()
                ->where('tenant_id', $tenant->id)
                ->orderBy('name')
                ->get(['id', 'name', 'type', 'color'])
                ->map(fn (Category $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'type' => $category->type->value,
                    'color' => $category->color,
                ])
                ->values()
                ->all(),
            'contactOptions' => Contact::query()
                ->where('tenant_id', $tenant->id)
                ->orderBy('name')
                ->get(['id', 'name', 'type'])
                ->map(fn (Contact $contact): array => [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'type' => $contact->type->value,
                ])
                ->values()
                ->all(),
            // Empty until the workspace registers its first service, which is what
            // keeps the breakdown out of the way of everyone who does not sell one.
            'serviceOptions' => Service::query()
                ->where('tenant_id', $tenant->id)
                ->orderBy('name')
                ->get(['id', 'name', 'default_price', 'currency_id', 'color'])
                ->map(fn (Service $service): array => [
                    'id' => $service->id,
                    'name' => $service->name,
                    'default_price' => $service->default_price,
                    'currency_id' => $service->currency_id,
                    'color' => $service->color,
                ])
                ->values()
                ->all(),
            'tagOptions' => Tag::query()
                ->where('tenant_id', $tenant->id)
                ->orderBy('name')
                ->get(['id', 'name', 'color'])
                ->map(fn (Tag $tag): array => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'color' => $tag->color,
                ])
                ->values()
                ->all(),
            'entries' => $entries->all(),
            'summaries' => $summaries->all(),
        ]);
    }

    /**
     * Attach the origin/destination account names to a projected entry.
     *
     * @param  array<string, mixed>  $entry
     * @param  array<int, array{from: ?string, to: ?string}>  $transferLegs
     * @return array<string, mixed>
     */
    private function withTransferLegs(array $entry, array $transferLegs): array
    {
        $legs = $transferLegs[$entry['transaction_id']] ?? null;

        $entry['transfer_from'] = $legs['from'] ?? null;
        $entry['transfer_to'] = $legs['to'] ?? null;

        return $entry;
    }

    /**
     * Map each transfer leg's transaction id to the origin/destination account
     * names, resolved from its sibling leg (same series_uuid).
     *
     * @param  Collection<int, Transaction>  $transactions
     * @return array<int, array{from: ?string, to: ?string}>
     */
    private function transferLegs(Collection $transactions): array
    {
        $bySeries = $transactions
            ->filter(fn (Transaction $transaction): bool => $transaction->is_transfer && $transaction->series_uuid !== null)
            ->groupBy('series_uuid')
            ->map(fn ($legs): array => [
                'from' => $legs->first(fn (Transaction $leg): bool => $leg->movement_type === TransactionMovementType::Expense)?->account?->name,
                'to' => $legs->first(fn (Transaction $leg): bool => $leg->movement_type === TransactionMovementType::Income)?->account?->name,
            ]);

        $legs = [];

        foreach ($transactions as $transaction) {
            if ($transaction->is_transfer && isset($bySeries[$transaction->series_uuid])) {
                $legs[$transaction->id] = $bySeries[$transaction->series_uuid];
            }
        }

        return $legs;
    }

    /**
     * Narrows the projected entries to what the filters asked for.
     *
     * Applied after projection rather than in SQL because a recurrence has no row
     * per occurrence to filter — the occurrences only exist once expanded.
     *
     * @param  Collection<int, array<string, mixed>>  $entries
     * @return Collection<int, array<string, mixed>>
     */
    private function filter(Collection $entries, IndexTransactionsRequest $request): Collection
    {
        $search = mb_strtolower($request->search());
        $movement = $request->movement();
        $status = $request->status();

        return $entries
            ->filter(function (array $entry) use ($search, $movement, $status): bool {
                if ($movement !== null && $entry['movement_type'] !== $movement->value) {
                    return false;
                }

                if ($status !== null && ($status === 'paid') !== ($entry['paid_at'] !== null)) {
                    return false;
                }

                // Description, the user's own note, and the account it moved
                // through — the three things someone actually remembers.
                $haystack = mb_strtolower(
                    $entry['description'].' '.($entry['note'] ?? '').' '.($entry['source'] ?? '')
                );

                return $search === '' || str_contains($haystack, $search);
            })
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $entries
     * @return Collection<int, array<string, mixed>>
     */
    private function sort(Collection $entries, string $order): Collection
    {
        return match ($order) {
            'date_asc' => $entries->sortBy([['date', 'asc'], ['label', 'asc']])->values(),
            // Case-insensitive, or every capitalised description would sort ahead
            // of every lowercase one instead of alphabetically.
            'name_asc' => $entries->sortBy(fn (array $entry): string => mb_strtolower($entry['description']))->values(),
            'name_desc' => $entries->sortByDesc(fn (array $entry): string => mb_strtolower($entry['description']))->values(),
            'amount_desc' => $entries->sortByDesc(fn (array $entry): float => (float) $entry['amount'])->values(),
            'amount_asc' => $entries->sortBy(fn (array $entry): float => (float) $entry['amount'])->values(),
            default => $entries->sortBy([['date', 'desc'], ['label', 'asc']])->values(),
        };
    }
}
