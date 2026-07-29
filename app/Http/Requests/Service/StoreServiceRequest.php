<?php

namespace App\Http\Requests\Service;

use App\Enums\LabelColor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class StoreServiceRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:60', $this->uniqueNameRule()],
            'color' => LabelColor::validationRules(),
            ...$this->priceRules(),
        ];
    }

    /**
     * A hand-picked colour is folded to lowercase, so the same colour is never
     * stored two ways depending on how it was typed.
     */
    protected function prepareForValidation(): void
    {
        $color = $this->input('color');

        if (is_string($color)) {
            $this->merge(['color' => LabelColor::normalize($color)]);
        }
    }

    protected function uniqueNameRule(): Unique
    {
        return Rule::unique('services', 'name')->where(fn ($query) => $query
            ->where('tenant_id', $this->user()?->tenant_id));
    }

    /**
     * The standing price is optional — plenty of work is quoted per job — but a
     * price without a currency could never be offered on a line, so the two
     * travel together. Only currencies the workspace already keeps an account in
     * are accepted: a price in a currency you never handle has nowhere to land.
     *
     * @return array<string, mixed>
     */
    protected function priceRules(): array
    {
        return [
            'default_price' => ['nullable', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999999999999.99'],
            'currency_id' => [
                Rule::requiredIf($this->filled('default_price')),
                'nullable',
                'integer',
                Rule::exists('accounts', 'currency_id')->where(fn ($query) => $query
                    ->where('tenant_id', $this->user()?->tenant_id)),
            ],
        ];
    }
}
