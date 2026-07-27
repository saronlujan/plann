<?php

namespace App\Http\Requests\Currency;

use App\Enums\PlanFeature;
use App\Models\Currency;
use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCurrenciesRequest extends FormRequest
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
            'currency_ids' => ['present', 'array', 'max:'.$this->activationLimit()],
            'currency_ids.*' => ['integer', 'exists:currencies,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'currency_ids.max' => __('currencies.errors.plan_limit'),
        ];
    }

    /**
     * How many currencies this workspace may keep active.
     *
     * Pro is unlimited. Basic is one — except that a workspace which already has
     * more (because it downgraded from Pro) keeps them: taking currencies away
     * would hide accounts and transactions the user still owns. It simply cannot
     * grow past what it already had.
     */
    private function activationLimit(): int
    {
        $tenant = $this->user()?->tenant;

        if (! $tenant instanceof Tenant) {
            return 1;
        }

        if ($tenant->hasFeature(PlanFeature::MultiCurrency)) {
            return Currency::query()->count();
        }

        return max(1, $tenant->activeCurrencies()->count());
    }
}
