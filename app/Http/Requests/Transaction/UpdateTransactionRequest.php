<?php

namespace App\Http\Requests\Transaction;

use App\Enums\TransactionMovementType;
use App\Enums\TransactionRecurrenceScope;
use Illuminate\Validation\Rule;

class UpdateTransactionRequest extends StoreTransactionRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            // Becoming a transfer means growing a second leg in another account,
            // which an update cannot do — that is a new transfer, not an edit.
            'movement_type' => [
                'required',
                Rule::enum(TransactionMovementType::class),
                Rule::notIn([TransactionMovementType::Transfer->value]),
            ],
            'recurrence_scope' => ['nullable', Rule::enum(TransactionRecurrenceScope::class)],
            'occurrence_date' => ['nullable', 'date_format:Y-m-d'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'movement_type.not_in' => __('transactions.errors.cannot_become_transfer'),
        ];
    }
}
