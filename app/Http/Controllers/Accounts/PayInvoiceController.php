<?php

namespace App\Http\Controllers\Accounts;

use App\Enums\TransactionMovementType;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\PayInvoiceRequest;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PayInvoiceController extends Controller
{
    public function __invoke(PayInvoiceRequest $request, Account $account): RedirectResponse
    {
        abort_unless($account->isCreditCard(), 404);

        $validated = $request->validated();
        $bank = Account::query()->findOrFail($validated['account_id']);
        $seriesUuid = (string) Str::uuid();

        DB::transaction(function () use ($validated, $bank, $account, $seriesUuid): void {
            $description = $account->name;

            Transaction::query()->create([
                'tenant_id' => $bank->tenant_id,
                'account_id' => $bank->id,
                'currency_id' => $account->currency_id,
                'movement_type' => TransactionMovementType::Expense->value,
                'is_transfer' => true,
                'type' => TransactionType::Unique->value,
                'series_uuid' => $seriesUuid,
                'effective_date' => $validated['effective_date'],
                'paid_at' => $validated['effective_date'],
                'amount' => $validated['amount'],
                'adjustment_amount' => 0,
                'description' => $description,
            ]);

            Transaction::query()->create([
                'tenant_id' => $account->tenant_id,
                'account_id' => $account->id,
                'currency_id' => $account->currency_id,
                'movement_type' => TransactionMovementType::Income->value,
                'is_transfer' => true,
                'type' => TransactionType::Unique->value,
                'series_uuid' => $seriesUuid,
                'effective_date' => $validated['effective_date'],
                'paid_at' => $validated['effective_date'],
                'amount' => $validated['amount'],
                'adjustment_amount' => 0,
                'description' => $description,
            ]);
        });

        return to_route('accounts.show', $account->id);
    }
}
