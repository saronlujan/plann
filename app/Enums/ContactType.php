<?php

namespace App\Enums;

enum ContactType: string
{
    case Provider = 'provider';

    case Client = 'client';

    case Partner = 'partner';

    case Platform = 'platform';

    public function label(): string
    {
        return __('enums.contact_type.'.$this->value);
    }
}
