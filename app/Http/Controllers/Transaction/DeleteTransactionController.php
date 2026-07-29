<?php

namespace App\Http\Controllers\Transaction;

use App\Enums\TransactionRecurrenceScope;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\DeleteTransactionRequest;
use App\Models\Transaction;
use App\Support\Transactions\TransactionAttachments;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class DeleteTransactionController extends Controller
{
    public function __construct(private readonly TransactionAttachments $attachments) {}

    public function __invoke(DeleteTransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        $this->authorize('delete', $transaction);

        $validated = $request->validated();
        $period = $transaction->effective_date->format('Y-m');

        if ($transaction->type === TransactionType::Recurring) {
            $scope = TransactionRecurrenceScope::from(
                $validated['recurrence_scope'] ?? TransactionRecurrenceScope::All->value,
            );

            $occurrence = CarbonImmutable::createFromFormat(
                'Y-m-d',
                $validated['occurrence_date'] ?? $transaction->effective_date->toDateString(),
            );

            if ($scope === TransactionRecurrenceScope::One) {
                return $this->removeSingleOccurrence($transaction, $occurrence);
            }

            if ($scope === TransactionRecurrenceScope::Forward) {
                return $this->removeCurrentAndFollowing($transaction, $occurrence);
            }

            return $this->removeSeries($transaction, $period);
        }

        $this->attachments->delete($transaction->attachment, $transaction->tenant_id);

        $transaction->delete();

        return to_route('transactions.index', ['period' => $period]);
    }

    /**
     * Takes one month out of the series without touching the rest of it.
     *
     * The master row is what the projector expands, so a single occurrence has
     * nothing of its own to delete — the month has to be recorded as removed
     * instead, and that marker is what stops it being generated again.
     */
    private function removeSingleOccurrence(Transaction $transaction, CarbonImmutable $occurrence): RedirectResponse
    {
        $series = $transaction->series_uuid ?? (string) $transaction->id;

        DB::transaction(function () use ($transaction, $occurrence, $series): void {
            if ($transaction->series_uuid === null) {
                $transaction->update(['series_uuid' => $series]);
            }

            // An occurrence the user had already edited is a real row: drop it
            // and leave the marker in its place, or the month would come back.
            Transaction::query()
                ->where('series_uuid', $series)
                ->whereNotNull('adjustment_month')
                ->whereBetween('adjustment_month', [
                    $occurrence->startOfMonth()->toDateString(),
                    $occurrence->endOfMonth()->toDateString(),
                ])
                ->delete();

            Transaction::query()->create([
                'tenant_id' => $transaction->tenant_id,
                'account_id' => $transaction->account_id,
                'currency_id' => $transaction->currency_id,
                'category_id' => $transaction->category_id,
                'movement_type' => $transaction->movement_type?->value,
                'type' => TransactionType::Recurring->value,
                'series_uuid' => $series,
                'is_skipped' => true,
                'effective_date' => $transaction->effective_date->toDateString(),
                'adjustment_month' => $occurrence->toDateString(),
                'amount' => $transaction->amount,
                'description' => $transaction->description,
            ]);
        });

        return to_route('transactions.index', ['period' => $occurrence->format('Y-m')]);
    }

    /**
     * Ends the series the day before the chosen occurrence.
     *
     * Closing the master is not enough on its own: a split made by an earlier
     * "this and the next" edit is a row of its own, and so is every occurrence
     * that was edited individually.
     */
    private function removeCurrentAndFollowing(Transaction $transaction, CarbonImmutable $occurrence): RedirectResponse
    {
        $series = $transaction->series_uuid;
        $previousDay = $occurrence->subDay()->toDateString();

        DB::transaction(function () use ($transaction, $occurrence, $series, $previousDay): void {
            $scoped = fn () => $series === null
                ? Transaction::query()->whereKey($transaction->getKey())
                : Transaction::query()->where('series_uuid', $series);

            $scoped()
                ->whereNotNull('adjustment_month')
                ->where('adjustment_month', '>=', $occurrence->startOfMonth()->toDateString())
                ->delete();

            // A series that only ever started here has nothing left to run.
            $scoped()
                ->whereNull('adjustment_month')
                ->where('effective_date', '>=', $occurrence->toDateString())
                ->delete();

            $scoped()
                ->whereNull('adjustment_month')
                ->where('effective_date', '<', $occurrence->toDateString())
                ->update(['effective_until' => $previousDay]);
        });

        return to_route('transactions.index', ['period' => $occurrence->format('Y-m')]);
    }

    /**
     * @param  string  $period  The month to land on once the series is gone.
     */
    private function removeSeries(Transaction $transaction, string $period): RedirectResponse
    {
        $series = $transaction->series_uuid;

        $rows = $series === null
            ? collect([$transaction])
            : Transaction::query()->where('series_uuid', $series)->get();

        DB::transaction(function () use ($rows): void {
            foreach ($rows as $row) {
                $this->attachments->delete($row->attachment, $row->tenant_id);

                $row->delete();
            }
        });

        return to_route('transactions.index', ['period' => $period]);
    }
}
