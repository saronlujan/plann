<?php

namespace App\Http\Requests\Category;

class UpdateCategoryRequest extends StoreCategoryRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        $rules['name'] = ['required', 'string', 'max:60', $this->uniqueNameRule()->ignore($this->route('category'))];

        return $rules;
    }
}
