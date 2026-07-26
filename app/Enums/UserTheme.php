<?php

namespace App\Enums;

enum UserTheme: string
{
    case Light = 'light';

    case Dark = 'dark';

    public function label(): string
    {
        return match ($this) {
            self::Light => 'Claro',
            self::Dark => 'Escuro',
        };
    }
}
