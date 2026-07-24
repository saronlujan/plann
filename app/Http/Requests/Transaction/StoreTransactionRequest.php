<?php

namespace App\Http\Requests\Transaction;

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
            'movement_type' => ['required', Rule::in(['income', 'expense'])],
            'type' => ['required', Rule::in(['unique', 'recurring', 'installment'])],
            'description' => ['required', 'string', 'max:255'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'account_id' => [
                'integer',
                'required',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('currency_id', $currencyId)),
            ],
            'effective_date' => ['required', 'date_format:Y-m-d'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'adjustment_amount' => ['nullable', 'numeric', 'gte:0'],
            'adjustment_month' => ['nullable', 'date_format:Y-m-d'],
            'installment_frequency' => ['nullable', Rule::in(['weekly', 'biweekly', 'monthly'])],
            'installments_total' => ['nullable', 'integer', 'min:1'],
            'installment_number' => ['nullable', 'integer', 'min:1'],
            'effective_until' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
