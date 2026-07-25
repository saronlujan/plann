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

        return [
            'movement_type' => ['required', Rule::enum(TransactionMovementType::class)],
            'type' => ['required', Rule::enum(TransactionType::class)],
            'description' => ['required', 'string', 'max:255'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'account_id' => [
                'integer',
                'required',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('currency_id', $currencyId)),
            ],
            'destination_account_id' => [
                Rule::requiredIf($this->string('movement_type')->toString() === TransactionMovementType::Transfer->value),
                'nullable',
                'integer',
                'different:account_id',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('currency_id', $currencyId)),
            ],
            'effective_date' => ['required', 'date_format:Y-m-d'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'adjustment_amount' => ['nullable', 'numeric', 'gte:0'],
            'adjustment_month' => ['nullable', 'date_format:Y-m-d'],
            'interest_amount' => ['nullable', 'numeric', 'gte:0'],
            'installment_frequency' => ['nullable', Rule::enum(TransactionInstallmentFrequency::class)],
            'installments_total' => ['nullable', 'integer', 'min:1'],
            'installment_number' => ['nullable', 'integer', 'min:1'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx'],
            'effective_until' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
