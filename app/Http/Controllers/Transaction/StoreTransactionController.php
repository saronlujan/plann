<?php

namespace App\Http\Controllers\Transaction;

use App\Enums\TransactionMovementType;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Models\Account;
use App\Models\Transaction;
use App\Support\Transactions\TransactionAttachments;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StoreTransactionController extends Controller
{
    public function __construct(private readonly TransactionAttachments $attachments) {}

    public function __invoke(StoreTransactionRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if ($validated['movement_type'] === TransactionMovementType::Transfer->value) {
            return $this->storeTransfer($validated, $request);
        }

        $transaction = Transaction::query()->create($this->payload($validated, $request));
        $transaction->tags()->sync($validated['tags'] ?? []);

        return to_route('transactions.index', [
            'period' => $transaction->effective_date->format('Y-m'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function payload(array $validated, StoreTransactionRequest $request): array
    {
        return [
            'account_id' => $validated['account_id'] ?? null,
            'currency_id' => $validated['currency_id'],
            'category_id' => $validated['category_id'] ?? null,
            'movement_type' => $validated['movement_type'],
            'type' => $validated['type'],
            'installment_frequency' => $validated['installment_frequency'] ?? null,
            'installments_total' => $validated['installments_total'] ?? null,
            'installment_number' => $validated['installment_number'] ?? null,
            'interest_amount' => $validated['interest_amount'] ?? null,
            'attachment_path' => $this->attachments->store($request->file('attachment')),
            'series_uuid' => in_array($validated['type'], [TransactionType::Recurring->value, TransactionType::Installment->value], true) ? (string) Str::uuid() : null,
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
    private function storeTransfer(array $validated, StoreTransactionRequest $request): RedirectResponse
    {
        $sourceAccount = Account::query()->findOrFail((int) $validated['account_id']);
        $destinationAccount = Account::query()->findOrFail((int) $validated['destination_account_id']);
        $seriesUuid = (string) Str::uuid();
        $attachmentPath = $this->attachments->store($request->file('attachment'));

        // Naming a transfer is optional: both legs still need a description, and
        // the list already shows which accounts are involved underneath it.
        $description = trim((string) ($validated['description'] ?? '')) ?: __('transactions.defaults.transfer_description');

        DB::transaction(function () use ($validated, $description, $sourceAccount, $destinationAccount, $seriesUuid, $attachmentPath): void {
            Transaction::query()->create([
                'tenant_id' => $sourceAccount->tenant_id,
                'account_id' => $sourceAccount->id,
                'currency_id' => $validated['currency_id'],
                'movement_type' => TransactionMovementType::Expense->value,
                'is_transfer' => true,
                'type' => TransactionType::Unique->value,
                'interest_amount' => $validated['interest_amount'] ?? null,
                'attachment_path' => $attachmentPath,
                'series_uuid' => $seriesUuid,
                'effective_date' => $validated['effective_date'],
                'effective_until' => null,
                'adjustment_month' => null,
                'paid_at' => $validated['effective_date'],
                'amount' => $validated['amount'],
                'adjustment_amount' => $validated['adjustment_amount'] ?? 0,
                'description' => $description,
            ]);

            Transaction::query()->create([
                'tenant_id' => $destinationAccount->tenant_id,
                'account_id' => $destinationAccount->id,
                'currency_id' => $validated['currency_id'],
                'movement_type' => TransactionMovementType::Income->value,
                'is_transfer' => true,
                'type' => TransactionType::Unique->value,
                'interest_amount' => null,
                'attachment_path' => null,
                'series_uuid' => $seriesUuid,
                'effective_date' => $validated['effective_date'],
                'effective_until' => null,
                'adjustment_month' => null,
                'paid_at' => $validated['effective_date'],
                'amount' => $validated['amount'],
                'adjustment_amount' => 0,
                'description' => $description,
            ]);
        });

        return to_route('transactions.index', [
            'period' => CarbonImmutable::createFromFormat('Y-m-d', $validated['effective_date'])->format('Y-m'),
        ]);
    }
}
