<?php

namespace App\Http\Requests\Preferences;

use App\Enums\UserColor;
use App\Enums\UserTheme;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePreferencesRequest extends FormRequest
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
        // Partial updates are allowed (e.g. the header language switcher sends only "locale").
        return [
            'locale' => ['sometimes', 'required', Rule::in(['pt', 'en', 'es'])],
            'theme' => ['sometimes', 'required', Rule::enum(UserTheme::class)],
            'color' => ['sometimes', 'required', Rule::enum(UserColor::class)],
        ];
    }
}
