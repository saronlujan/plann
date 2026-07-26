<?php

namespace App\Http\Controllers\Transaction;

use App\Enums\TransactionRecurrenceScope;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\UpdateTransactionRequest;
use App\Models\Transaction;
use App\Support\Transactions\TransactionAttachments;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpdateTransactionController extends Controller
{
    public function __construct(private readonly TransactionAttachments $attachments) {}

    public function __invoke(UpdateTransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        $this->authorize('update', $transaction);

        $validated = $request->validated();

        $newAttachment = $request->file('attachment');
        $attachmentPath = $this->attachments->store($newAttachment) ?? $transaction->attachment_path;

        if ($transaction->type === TransactionType::Recurring) {
            $recurrenceScope = TransactionRecurrenceScope::from($validated['recurrence_scope'] ?? TransactionRecurrenceScope::All->value);

            if ($recurrenceScope === TransactionRecurrenceScope::One) {
                return $this->updateSingleOccurrence($transaction, $validated, $attachmentPath);
            }

            if ($recurrenceScope === TransactionRecurrenceScope::Forward) {
                return $this->updateCurrentAndFollowing($transaction, $validated, $attachmentPath);
            }
        }

        $previousAttachment = $transaction->attachment_path;

        $transaction->fill($this->payload($validated, $transaction, $attachmentPath));
        $transaction->save();
        $transaction->tags()->sync($validated['tags'] ?? []);

        $this->attachments->discardReplaced($newAttachment, $previousAttachment, $attachmentPath);

        return to_route('transactions.index', [
            'period' => $transaction->effective_date->format('Y-m'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function payload(array $validated, Transaction $transaction, ?string $attachmentPath): array
    {
        $scheduleType = $validated['type'];

        return [
            'account_id' => $validated['account_id'] ?? null,
            'currency_id' => $validated['currency_id'],
            'category_id' => $validated['category_id'] ?? null,
            'movement_type' => $validated['movement_type'],
            'type' => $scheduleType,
            'installment_frequency' => $validated['installment_frequency'] ?? null,
            'installments_total' => $validated['installments_total'] ?? null,
            'installment_number' => $validated['installment_number'] ?? null,
            'interest_amount' => $validated['interest_amount'] ?? $transaction->interest_amount,
            'attachment_path' => $attachmentPath,
            'series_uuid' => in_array($scheduleType, [TransactionType::Recurring->value, TransactionType::Installment->value], true)
                ? ($transaction->series_uuid ?? (string) Str::uuid())
                : null,
            'effective_date' => $validated['effective_date'],
            'effective_until' => $validated['effective_until'] ?? null,
            'adjustment_month' => $validated['adjustment_month'] ?? null,
            'amount' => $validated['amount'],
            'adjustment_amount' => $validated['adjustment_amount'] ?? 0,
            'description' => $validated['description'],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function updateSingleOccurrence(Transaction $transaction, array $validated, ?string $attachmentPath): RedirectResponse
    {
        $occurrenceDate = CarbonImmutable::createFromFormat('Y-m-d', $validated['occurrence_date'] ?? $validated['effective_date']);
        $seriesUuid = $transaction->series_uuid ?? (string) Str::uuid();

        DB::transaction(function () use ($transaction, $validated, $attachmentPath, $occurrenceDate, $seriesUuid): void {
            if ($transaction->series_uuid === null) {
                $transaction->update(['series_uuid' => $seriesUuid]);
            }

            $occurrence = Transaction::query()->create([
                'tenant_id' => $transaction->tenant_id,
                'account_id' => $validated['account_id'] ?? $transaction->account_id,
                'currency_id' => $validated['currency_id'],
                'category_id' => $validated['category_id'] ?? $transaction->category_id,
                'movement_type' => $validated['movement_type'],
                'type' => TransactionType::Recurring->value,
                'installment_frequency' => null,
                'installments_total' => null,
                'installment_number' => null,
                'interest_amount' => $validated['interest_amount'] ?? $transaction->interest_amount,
                'attachment_path' => $attachmentPath,
                'series_uuid' => $seriesUuid,
                'effective_date' => $transaction->effective_date->toDateString(),
                'effective_until' => null,
                'adjustment_month' => $occurrenceDate->toDateString(),
                'amount' => $validated['amount'],
                'adjustment_amount' => $validated['adjustment_amount'] ?? 0,
                'description' => $validated['description'],
            ]);

            $occurrence->tags()->sync($validated['tags'] ?? []);
        });

        return to_route('transactions.index', [
            'period' => $occurrenceDate->format('Y-m'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function updateCurrentAndFollowing(Transaction $transaction, array $validated, ?string $attachmentPath): RedirectResponse
    {
        $occurrenceDate = CarbonImmutable::createFromFormat('Y-m-d', $validated['occurrence_date'] ?? $validated['effective_date']);
        $effectiveUntil = $occurrenceDate->subDay()->toDateString();
        $seriesUuid = $transaction->series_uuid ?? (string) Str::uuid();

        $newSeries = DB::transaction(function () use ($transaction, $validated, $attachmentPath, $occurrenceDate, $effectiveUntil, $seriesUuid): Transaction {
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
                'movement_type' => $validated['movement_type'],
                'type' => TransactionType::Recurring->value,
                'installment_frequency' => null,
                'installments_total' => null,
                'installment_number' => null,
                'interest_amount' => $validated['interest_amount'] ?? $transaction->interest_amount,
                'attachment_path' => $attachmentPath,
                'series_uuid' => $seriesUuid,
                'effective_date' => $occurrenceDate->toDateString(),
                'effective_until' => null,
                'adjustment_month' => null,
                'amount' => $validated['amount'],
                'adjustment_amount' => $validated['adjustment_amount'] ?? 0,
                'description' => $validated['description'],
            ]);

            $following->tags()->sync($validated['tags'] ?? []);

            return $following;
        });

        return to_route('transactions.index', [
            'period' => $newSeries->effective_date->format('Y-m'),
        ]);
    }
}
