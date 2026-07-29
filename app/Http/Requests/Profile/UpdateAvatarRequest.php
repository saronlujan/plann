<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * The crop is a region in the source image's own pixels. It is clamped
     * server-side too, so a bad rectangle cannot reach the image library.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'avatar' => ['required', 'file', 'image', 'max:10240', 'mimes:jpg,jpeg,png,webp'],
            'crop_x' => ['required', 'integer', 'min:0'],
            'crop_y' => ['required', 'integer', 'min:0'],
            'crop_size' => ['required', 'integer', 'min:1'],
        ];
    }
}
