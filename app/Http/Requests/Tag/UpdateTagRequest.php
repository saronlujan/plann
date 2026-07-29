<?php

namespace App\Http\Requests\Tag;

use App\Enums\LabelColor;

class UpdateTagRequest extends StoreTagRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:40', $this->uniqueNameRule()->ignore($this->route('tag'))],
            'color' => LabelColor::validationRules(),
        ];
    }
}
