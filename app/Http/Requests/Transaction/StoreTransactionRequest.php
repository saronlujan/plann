<?php

namespace App\Http\Requests\Transaction;

use App\Enums\TransactionInstallmentFrequency;
use App\Enums\TransactionMovementType;
use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $currencyId = $this->integer('currency_id');
        $tenantId = $this->user()?->tenant_id;
        $isTransfer = $this->string('movement_type')->toString() === TransactionMovementType::Transfer->value;
        $isInstallment = $this->string('type')->toString() === TransactionType::Installment->value;

        return [
            'movement_type' => ['required', Rule::enum(TransactionMovementType::class)],
            'type' => ['required', Rule::enum(TransactionType::class)],
            'description' => ['required', 'string', 'max:255'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'account_id' => [
                'integer',
                'required',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query
                    ->where('currency_id', $currencyId)
                    ->where('tenant_id', $tenantId)),
            ],
            'destination_account_id' => [
                Rule::requiredIf($isTransfer),
                'nullable',
                'integer',
                'different:account_id',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query
                    ->where('currency_id', $currencyId)
                    ->where('tenant_id', $tenantId)),
            ],
            'effective_date' => ['required', 'date_format:Y-m-d'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999999999999.99'],
            'adjustment_amount' => ['nullable', 'numeric', 'decimal:0,2', 'gte:0'],
            'adjustment_month' => ['nullable', 'date_format:Y-m-d'],
            'interest_amount' => ['nullable', 'numeric', 'decimal:0,2', 'gte:0'],
            'installment_frequency' => [
                Rule::requiredIf($isInstallment),
                'nullable',
                Rule::enum(TransactionInstallmentFrequency::class),
            ],
            'installments_total' => [
                Rule::requiredIf($isInstallment),
                'nullable',
                'integer',
                'min:1',
                'max:600',
            ],
            'installment_number' => ['nullable', 'integer', 'min:1'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx'],
            'effective_until' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:effective_date'],
        ];
    }
}
