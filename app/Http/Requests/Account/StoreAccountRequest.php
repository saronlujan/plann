<?php

namespace App\Http\Requests\Account;

use App\Enums\AccountKind;
use App\Enums\PlanFeature;
use Illuminate\Contracts\Validation\Validator;
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
            // The whole catalogue the workspace can see: opening an account in a
            // currency is what puts that currency to use, so it cannot be limited
            // to the ones already in use.
            'currency_id' => [
                'required',
                'integer',
                Rule::exists('currencies', 'id')->where(fn ($query) => $query
                    ->where(fn ($scope) => $scope
                        ->whereNull('tenant_id')
                        ->orWhere('tenant_id', $tenantId))),
            ],
            'credit_limit' => [Rule::requiredIf($isCard), 'nullable', 'numeric', 'decimal:0,2', 'gt:0'],
            'closing_day' => [Rule::requiredIf($isCard), 'nullable', 'integer', 'between:1,31'],
            'due_day' => [Rule::requiredIf($isCard), 'nullable', 'integer', 'between:1,31'],
        ];
    }

    /**
     * Holding more than one currency is where the plan bites.
     *
     * Checked after the field rules so the message lands on the currency itself,
     * and only when the account brings in a currency the workspace is not already
     * entitled to — adding another account in the same one is always allowed.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $tenant = $this->user()?->tenant()->first();
            $currencyId = $this->integer('currency_id');

            if ($tenant === null || $currencyId === 0) {
                return;
            }

            if ($tenant->hasFeature(PlanFeature::MultiCurrency)) {
                return;
            }

            $allowed = $tenant->activeCurrencies()->pluck('currencies.id')->all();

            // Before the first account nothing is in use, so the currency chosen
            // at signup is what the plan holds the workspace to.
            if ($allowed === []) {
                $allowed = array_filter([$tenant->currency_id]);
            }

            if ($allowed === [] || in_array($currencyId, $allowed, true)) {
                return;
            }

            $validator->errors()->add('currency_id', __('accounts.errors.plan_limit'));
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('kind') === null || $this->input('kind') === '') {
            $this->merge(['kind' => AccountKind::Account->value]);
        }

        // An account starts empty: the money that was already there is recorded
        // as an ordinary transaction, like every other movement.
        if ($this->string('kind')->toString() === AccountKind::CreditCard->value) {
            return;
        }

        // Non-card accounts never carry credit-card attributes.
        $this->merge([
            'credit_limit' => null,
            'closing_day' => null,
            'due_day' => null,
        ]);
    }
}
