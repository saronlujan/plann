<?php

namespace App\Enums;

enum ContactType: string
{
    case Provider = 'provider';

    case Client = 'client';

    public function label(): string
    {
        return match ($this) {
            self::Provider => 'Fornecedor',
            self::Client => 'Cliente',
        };
    }
}
