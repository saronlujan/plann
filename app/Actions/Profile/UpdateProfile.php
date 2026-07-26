<?php

namespace App\Actions\Profile;

use App\Models\User;

class UpdateProfile
{
    /**
     * Persist the editable profile fields.
     *
     * The email is not among them: it is the verified login identity, so it is
     * whitelisted out here as well as being absent from the validation rules.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $user, array $data): void
    {
        $user->update(array_intersect_key($data, array_flip([
            'name',
            'phone',
        ])));
    }
}
