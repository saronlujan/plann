<?php

namespace App\Http\Requests\Service;

use App\Enums\LabelColor;

class UpdateServiceRequest extends StoreServiceRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60', $this->uniqueNameRule()->ignore($this->route('service'))],
            'color' => LabelColor::validationRules(),
            ...$this->priceRules(),
        ];
    }
}
