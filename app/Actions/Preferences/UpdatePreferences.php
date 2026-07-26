<?php

namespace App\Actions\Preferences;

use App\Models\User;

class UpdatePreferences
{
    /**
     * Persist whichever appearance/language preferences were provided.
     *
     * @param  array{locale?: string, theme?: string, color?: string}  $data
     */
    public function handle(User $user, array $data): void
    {
        $user->update(array_intersect_key($data, array_flip(['locale', 'theme', 'color'])));
    }
}
