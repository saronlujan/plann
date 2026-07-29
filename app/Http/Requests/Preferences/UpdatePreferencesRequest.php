<?php

namespace App\Http\Requests\Preferences;

use App\Enums\SoundTheme;
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
            'sound_enabled' => ['sometimes', 'required', 'boolean'],
            'sound_theme' => ['sometimes', 'required', Rule::enum(SoundTheme::class)],
            'notifications_enabled' => ['sometimes', 'required', 'boolean'],
            'notify_days_before' => ['sometimes', 'required', 'integer', 'min:0', 'max:30'],
            // Only a currency the workspace holds an account in; null means "no
            // preference" and falls back to the first one in use.
            'default_currency_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('accounts', 'currency_id')
                    ->where('tenant_id', $this->user()?->tenant_id),
            ],
        ];
    }
}
