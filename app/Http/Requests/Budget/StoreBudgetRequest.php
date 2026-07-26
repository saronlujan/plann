<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

class StoreBudgetRequest extends FormRequest
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
            'category_id' => [
                'required',
                'integer',
                $this->categoryRule(),
                $this->uniqueRule(),
            ],
            'currency_id' => [
                'required',
                'integer',
                Rule::exists('currency_tenant', 'currency_id')->where(fn ($query) => $query
                    ->where('tenant_id', $tenantId)
                    ->where('is_active', true)),
            ],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999999999999.99'],
        ];
    }

    protected function categoryRule(): Exists
    {
        // Budgets track spending, so both expense and dual-use ("both") categories qualify.
        return Rule::exists('categories', 'id')->where(fn ($query) => $query
            ->where('tenant_id', $this->user()?->tenant_id)
            ->whereIn('type', ['expense', 'both']));
    }

    protected function uniqueRule(): Unique
    {
        return Rule::unique('budgets', 'category_id')->where(fn ($query) => $query
            ->where('tenant_id', $this->user()?->tenant_id)
            ->where('currency_id', $this->integer('currency_id')));
    }
}
