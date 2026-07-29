<?php

namespace App\Enums;

/**
 * Listed with the deferring option first, which is what the picker offers first
 * and what most people want: follow whatever the device is already set to.
 */
enum UserTheme: string
{
    case System = 'system';

    case Light = 'light';

    case Dark = 'dark';

    public function label(): string
    {
        return __('enums.user_theme.'.$this->value);
    }
}
