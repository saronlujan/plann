<?php

namespace App\Http\Requests\Register;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:40'],
            'country_code' => ['required', 'string', 'size:2', Rule::exists('countries', 'code')->where('is_active', true)],
            // Only the shared catalogue: a workspace that does not exist yet cannot
            // have a currency of its own.
            'currency_code' => [
                'required',
                'string',
                'max:4',
                Rule::exists('currencies', 'code')->whereNull('tenant_id'),
            ],
            // The plan picked at signup: it decides what the 14-day trial shows.
            'plan_slug' => [
                'required',
                'string',
                Rule::exists('plans', 'slug')->where('is_active', true),
            ],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ];
    }
}
