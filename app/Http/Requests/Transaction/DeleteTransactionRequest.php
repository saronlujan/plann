<?php

namespace App\Http\Requests\Transaction;

use App\Enums\TransactionRecurrenceScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * How much of a recurring series to remove, and which occurrence the user
     * was looking at when they asked.
     *
     * Both are optional: everything that is not a series has a single row to
     * delete, and the scope then has nothing to choose between.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'recurrence_scope' => ['nullable', Rule::enum(TransactionRecurrenceScope::class)],
            'occurrence_date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
