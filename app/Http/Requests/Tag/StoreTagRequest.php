<?php

namespace App\Http\Requests\Tag;

use App\Enums\LabelColor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class StoreTagRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:40', $this->uniqueNameRule()],
            'color' => ['required', Rule::enum(LabelColor::class)],
        ];
    }

    protected function uniqueNameRule(): Unique
    {
        return Rule::unique('tags', 'name')->where(fn ($query) => $query
            ->where('tenant_id', $this->user()?->tenant_id));
    }
}
