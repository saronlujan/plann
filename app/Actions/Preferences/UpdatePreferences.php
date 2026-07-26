<?php

namespace App\Actions\Preferences;

use App\Models\User;

class UpdatePreferences
{
    /**
     * Persist whichever appearance/language preferences were provided.
     *
     * @param  array{locale?: string, theme?: string, color?: string, sound_enabled?: bool, sound_theme?: string, notifications_enabled?: bool, notify_days_before?: int}  $data
     */
    public function handle(User $user, array $data): void
    {
        $user->update(array_intersect_key($data, array_flip([
            'locale',
            'theme',
            'color',
            'sound_enabled',
            'sound_theme',
            'notifications_enabled',
            'notify_days_before',
        ])));
    }
}
