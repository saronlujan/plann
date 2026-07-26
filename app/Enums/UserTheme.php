<?php

namespace App\Enums;

enum UserTheme: string
{
    case Light = 'light';

    case Dark = 'dark';

    public function label(): string
    {
        return __('enums.user_theme.'.$this->value);
    }
}
