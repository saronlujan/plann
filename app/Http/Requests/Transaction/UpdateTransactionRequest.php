<?php

namespace App\Http\Requests\Transaction;

use App\Enums\TransactionRecurrenceScope;
use Illuminate\Validation\Rule;

class UpdateTransactionRequest extends StoreTransactionRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'recurrence_scope' => ['nullable', Rule::enum(TransactionRecurrenceScope::class)],
            'occurrence_date' => ['nullable', 'date_format:Y-m-d'],
        ]);
    }
}
