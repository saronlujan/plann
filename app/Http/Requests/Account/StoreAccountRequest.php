<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountRequest extends FormRequest
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
            'balance' => ['nullable', 'numeric', 'decimal:0,2'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('balance') === null || $this->input('balance') === '') {
            $this->merge(['balance' => 0]);
        }
    }
}
