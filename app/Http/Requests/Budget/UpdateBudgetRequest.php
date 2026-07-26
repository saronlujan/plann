<?php

namespace App\Http\Requests\Budget;

class UpdateBudgetRequest extends StoreBudgetRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        // Same guards, but the unique check ignores the budget being edited.
        $rules['category_id'] = [
            'required',
            'integer',
            $this->categoryRule(),
            $this->uniqueRule()->ignore($this->route('budget')),
        ];

        return $rules;
    }
}
