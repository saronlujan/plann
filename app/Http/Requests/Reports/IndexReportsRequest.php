<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;

class IndexReportsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Every filter is optional: opening the page with no query string must show
     * a sensible default report rather than a validation error.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'string', 'date_format:Y-m'],
            'to' => ['nullable', 'string', 'date_format:Y-m'],
            'currency_id' => ['nullable', 'integer'],
        ];
    }
}
