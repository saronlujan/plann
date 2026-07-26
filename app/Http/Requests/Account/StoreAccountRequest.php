<?php

namespace App\Http\Requests\Account;

use App\Enums\AccountKind;
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
        $isCard = $this->string('kind')->toString() === AccountKind::CreditCard->value;

        return [
            'name' => ['required', 'string', 'max:60'],
            'kind' => ['required', Rule::enum(AccountKind::class)],
            'currency_id' => [
                'required',
                'integer',
                Rule::exists('currency_tenant', 'currency_id')->where(fn ($query) => $query
                    ->where('tenant_id', $tenantId)
                    ->where('is_active', true)),
            ],
            'balance' => ['nullable', 'numeric', 'decimal:0,2'],
            'credit_limit' => [Rule::requiredIf($isCard), 'nullable', 'numeric', 'decimal:0,2', 'gt:0'],
            'closing_day' => [Rule::requiredIf($isCard), 'nullable', 'integer', 'between:1,31'],
            'due_day' => [Rule::requiredIf($isCard), 'nullable', 'integer', 'between:1,31'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('kind') === null || $this->input('kind') === '') {
            $this->merge(['kind' => AccountKind::Account->value]);
        }

        $isCard = $this->string('kind')->toString() === AccountKind::CreditCard->value;

        if ($isCard) {
            // A credit card is a liability: its balance stays 0 and movements flow
            // through transactions, not an opening balance.
            $this->merge(['balance' => 0]);

            return;
        }

        if ($this->input('balance') === null || $this->input('balance') === '') {
            $this->merge(['balance' => 0]);
        }

        // Non-card accounts never carry credit-card attributes.
        $this->merge([
            'credit_limit' => null,
            'closing_day' => null,
            'due_day' => null,
        ]);
    }
}
