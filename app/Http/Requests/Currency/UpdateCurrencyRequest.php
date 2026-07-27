<?php

namespace App\Http\Requests\Currency;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCurrencyRequest extends FormRequest
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
        $currency = $this->route('currency');

        return [
            'name' => ['required', 'string', 'max:255'],
            // Same guard as creating, minus this row: a currency may keep its own
            // code while still being unable to take one already in use.
            'code' => [
                'required',
                'string',
                'max:4',
                'uppercase',
                'alpha_num',
                Rule::unique('currencies', 'code')
                    ->ignore($currency)
                    ->where(fn ($query) => $query
                        ->whereNull('tenant_id')
                        ->orWhere('tenant_id', $this->user()?->tenant_id)),
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
