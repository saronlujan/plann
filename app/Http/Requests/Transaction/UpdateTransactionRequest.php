<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Validation\Rule;

class UpdateTransactionRequest extends StoreTransactionRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'recurrence_scope' => ['nullable', Rule::in(['all', 'one', 'forward'])],
            'occurrence_date' => ['nullable', 'date_format:Y-m-d'],
        ]);
    }
}
