<?php

namespace App\Http\Requests\Category;

use App\Enums\CategoryType;
use App\Enums\LabelColor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class StoreCategoryRequest extends FormRequest
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
            'type' => ['required', Rule::enum(CategoryType::class)],
            'color' => ['required', Rule::enum(LabelColor::class)],
        ];
    }

    protected function uniqueNameRule(): Unique
    {
        $tenantId = $this->user()?->tenant_id;
        $type = $this->string('type')->toString();

        return Rule::unique('categories', 'name')->where(fn ($query) => $query
            ->where('tenant_id', $tenantId)
            ->where('type', $type));
    }
}
