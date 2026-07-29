<?php

namespace App\Http\Controllers\Transaction;

use App\Enums\TransactionRecurrenceScope;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\UpdateTransactionRequest;
use App\Models\Transaction;
use App\Support\Transactions\TransactionAttachments;
use App\Support\Transactions\TransactionLines;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpdateTransactionController extends Controller
{
    public function __construct(
        private readonly TransactionAttachments $attachments,
        private readonly TransactionLines $lines,
    ) {}

    public function __invoke(UpdateTransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        $this->authorize('update', $transaction);

        $validated = $request->validated();

        $newAttachment = $request->file('attachment');
        $attachmentFile = $this->attachments->store($newAttachment) ?? $transaction->attachment;

        if ($transaction->type === TransactionType::Recurring) {
            $recurrenceScope = TransactionRecurrenceScope::from($validated['recurrence_scope'] ?? TransactionRecurrenceScope::All->value);

            if ($recurrenceScope === TransactionRecurrenceScope::One) {
                return $this->updateSingleOccurrence($transaction, $validated, $attachmentFile);
            }

            if ($recurrenceScope === TransactionRecurrenceScope::Forward) {
                return $this->updateCurrentAndFollowing($transaction, $validated, $attachmentFile);
            }
        }

        if ($this->leavesTransfer($transaction, $validated)) {
            $this->unpairTransfer($transaction);
        }

        $previousAttachment = $transaction->attachment;

        DB::transaction(function () use ($transaction, $validated, $attachmentFile): void {
            $transaction->fill($this->payload($validated, $transaction, $attachmentFile));
            $transaction->save();
            $transaction->tags()->sync($validated['tags'] ?? []);
            $this->lines->sync($transaction, $validated);
        });

        $this->attachments->discardReplaced($newAttachment, $previousAttachment, $attachmentFile);

        return to_route('transactions.index', [
            'period' => $transaction->effective_date->format('Y-m'),
        ]);
    }

    /**
     * When the entry was settled, from the form's yes/no.
     *
     * A request that omits the field leaves the existing state alone, so marking
     * something paid from the list is not undone by an unrelated edit.
     *
     * @param  array<string, mixed>  $validated
     */
    private function paidAt(array $validated, Transaction $transaction): ?string
    {
        if (! array_key_exists('paid', $validated)) {
            return $transaction->paid_at?->toDateString();
        }

        return $validated['paid'] ? $validated['effective_date'] : null;
    }

    /**
     * The series a transaction belongs to after the edit.
     *
     * On a transfer the series is what ties the two legs together, not a
     * recurrence — so a one-off transfer keeps it, or its pair would come apart
     * the first time someone corrected the amount.
     */
    private function resolveSeriesUuid(string $scheduleType, Transaction $transaction): ?string
    {
        if (in_array($scheduleType, [TransactionType::Recurring->value, TransactionType::Installment->value], true)) {
            return $transaction->series_uuid ?? (string) Str::uuid();
        }

        return $transaction->is_transfer ? $transaction->series_uuid : null;
    }

    /**
     * Whether this edit turns a transfer leg into a plain income or expense.
     *
     * Editing the amount of a transfer keeps its movement type, so only a real
     * change of type counts — otherwise every edit would break the pairing.
     *
     * @param  array<string, mixed>  $validated
     */
    private function leavesTransfer(Transaction $transaction, array $validated): bool
    {
        return $transaction->is_transfer
            && $validated['movement_type'] !== $transaction->movement_type?->value;
    }

    /**
     * Drops the transfer pairing from both legs.
     *
     * Half a transfer is not a transfer, so the other leg stops being one too.
     * Neither row is deleted: both movements really happened and still affect
     * their accounts, so removing one would silently change a balance. What is
     * left is an ordinary entry the user can edit or delete.
     */
    private function unpairTransfer(Transaction $transaction): void
    {
        $series = $transaction->series_uuid;

        if ($series !== null) {
            Transaction::query()
                ->where('series_uuid', $series)
                ->whereKeyNot($transaction->getKey())
                ->update(['is_transfer' => false, 'series_uuid' => null]);
        }

        // Left unsaved on purpose: the update below persists them.
        $transaction->is_transfer = false;
        $transaction->series_uuid = null;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function payload(array $validated, Transaction $transaction, ?string $attachmentFile): array
    {
        $scheduleType = $validated['type'];

        return [
            'account_id' => $validated['account_id'] ?? null,
            'currency_id' => $validated['currency_id'],
            'category_id' => $validated['category_id'] ?? null,
            'contact_id' => $validated['contact_id'] ?? null,
            'movement_type' => $validated['movement_type'],
            'type' => $scheduleType,
            'installment_frequency' => $validated['installment_frequency'] ?? null,
            'installments_total' => $validated['installments_total'] ?? null,
            'installment_number' => $validated['installment_number'] ?? null,
            'interest_amount' => $validated['interest_amount'] ?? $transaction->interest_amount,
            'attachment' => $attachmentFile,
            'series_uuid' => $this->resolveSeriesUuid($scheduleType, $transaction),
            'effective_date' => $validated['effective_date'],
            'effective_until' => $validated['effective_until'] ?? null,
            'adjustment_month' => $validated['adjustment_month'] ?? null,
            'paid_at' => $this->paidAt($validated, $transaction),
            'amount' => $this->lines->amountFor($validated),
            'adjustment_amount' => $validated['adjustment_amount'] ?? 0,
            'description' => $validated['description'] ?? $transaction->description,
            'note' => $validated['note'] ?? $transaction->note,
            'observations' => $validated['observations'] ?? $transaction->observations,
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function updateSingleOccurrence(Transaction $transaction, array $validated, ?string $attachmentFile): RedirectResponse
    {
        $occurrenceDate = CarbonImmutable::createFromFormat('Y-m-d', $validated['occurrence_date'] ?? $validated['effective_date']);
        $seriesUuid = $transaction->series_uuid ?? (string) Str::uuid();

        DB::transaction(function () use ($transaction, $validated, $attachmentFile, $occurrenceDate, $seriesUuid): void {
            if ($transaction->series_uuid === null) {
                $transaction->update(['series_uuid' => $seriesUuid]);
            }

            $occurrence = Transaction::query()->create([
                'tenant_id' => $transaction->tenant_id,
                'account_id' => $validated['account_id'] ?? $transaction->account_id,
                'currency_id' => $validated['currency_id'],
                'category_id' => $validated['category_id'] ?? $transaction->category_id,
                'contact_id' => $validated['contact_id'] ?? $transaction->contact_id,
                'movement_type' => $validated['movement_type'],
                'type' => TransactionType::Recurring->value,
                'installment_frequency' => null,
                'installments_total' => null,
                'installment_number' => null,
                'interest_amount' => $validated['interest_amount'] ?? $transaction->interest_amount,
                'attachment' => $attachmentFile,
                'series_uuid' => $seriesUuid,
                'effective_date' => $transaction->effective_date->toDateString(),
                'effective_until' => null,
                'adjustment_month' => $occurrenceDate->toDateString(),
                'paid_at' => $this->paidAt($validated, $transaction),
                'amount' => $this->lines->amountFor($validated),
                'adjustment_amount' => $validated['adjustment_amount'] ?? 0,
                'description' => $validated['description'] ?? $transaction->description,
                'note' => $validated['note'] ?? $transaction->note,
                'observations' => $validated['observations'] ?? $transaction->observations,
            ]);

            $occurrence->tags()->sync($validated['tags'] ?? []);
            $this->lines->sync($occurrence, $validated);
        });

        return to_route('transactions.index', [
            'period' => $occurrenceDate->format('Y-m'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function updateCurrentAndFollowing(Transaction $transaction, array $validated, ?string $attachmentFile): RedirectResponse
    {
        $occurrenceDate = CarbonImmutable::createFromFormat('Y-m-d', $validated['occurrence_date'] ?? $validated['effective_date']);
        $effectiveUntil = $occurrenceDate->subDay()->toDateString();
        $seriesUuid = $transaction->series_uuid ?? (string) Str::uuid();

        $newSeries = DB::transaction(function () use ($transaction, $validated, $attachmentFile, $occurrenceDate, $effectiveUntil, $seriesUuid): Transaction {
            if ($transaction->series_uuid === null) {
                $transaction->update(['series_uuid' => $seriesUuid]);
            }

            $transaction->update([
                'effective_until' => $effectiveUntil,
            ]);

            $following = Transaction::query()->create([
                'tenant_id' => $transaction->tenant_id,
                'account_id' => $validated['account_id'] ?? $transaction->account_id,
                'currency_id' => $validated['currency_id'],
                'category_id' => $validated['category_id'] ?? $transaction->category_id,
                'contact_id' => $validated['contact_id'] ?? $transaction->contact_id,
                'movement_type' => $validated['movement_type'],
                'type' => TransactionType::Recurring->value,
                'installment_frequency' => null,
                'installments_total' => null,
                'installment_number' => null,
                'interest_amount' => $validated['interest_amount'] ?? $transaction->interest_amount,
                'attachment' => $attachmentFile,
                'series_uuid' => $seriesUuid,
                'effective_date' => $occurrenceDate->toDateString(),
                'effective_until' => null,
                'adjustment_month' => null,
                'paid_at' => $this->paidAt($validated, $transaction),
                'amount' => $this->lines->amountFor($validated),
                'adjustment_amount' => $validated['adjustment_amount'] ?? 0,
                'description' => $validated['description'] ?? $transaction->description,
                'note' => $validated['note'] ?? $transaction->note,
                'observations' => $validated['observations'] ?? $transaction->observations,
            ]);

            $following->tags()->sync($validated['tags'] ?? []);
            $this->lines->sync($following, $validated);

            return $following;
        });

        return to_route('transactions.index', [
            'period' => $newSeries->effective_date->format('Y-m'),
        ]);
    }
}
