<?php

namespace App\Http\Requests\Goal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = $this->user()?->tenant_id;

        return [
            'name' => ['required', 'string', 'max:60'],
            'currency_id' => [
                'required',
                'integer',
                Rule::exists('currency_tenant', 'currency_id')->where(fn ($query) => $query
                    ->where('tenant_id', $tenantId)
                    ->where('is_active', true)),
            ],
            'target_amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999999999999.99'],
            'current_amount' => ['nullable', 'numeric', 'decimal:0,2', 'gte:0'],
            'target_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('current_amount') === null || $this->input('current_amount') === '') {
            $this->merge(['current_amount' => 0]);
        }
    }
}
