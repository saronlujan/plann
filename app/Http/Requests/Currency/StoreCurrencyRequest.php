<?php

namespace App\Http\Requests\Currency;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCurrencyRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            // Unique against the shared catalogue *and* this workspace's own
            // currencies, so a tenant cannot shadow BRL with its own "BRL".
            'code' => [
                'required',
                'string',
                'max:4',
                'uppercase',
                'alpha_num',
                Rule::unique('currencies', 'code')->where(
                    fn ($query) => $query
                        ->whereNull('tenant_id')
                        ->orWhere('tenant_id', $this->user()?->tenant_id),
                ),
            ],
            'symbol' => ['required', 'string', 'max:8'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => mb_strtoupper(trim($this->string('code')->toString()))]);
        }
    }
}
