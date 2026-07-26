<?php

namespace App\Enums;

enum UserColor: string
{
    case Blue = 'blue';

    case Indigo = 'indigo';

    case Purple = 'purple';

    case Pink = 'pink';

    case Cyan = 'cyan';

    case Orange = 'orange';

    case Yellow = 'yellow';

    case Green = 'green';

    case Teal = 'teal';

    case Zinc = 'zinc';

    public function label(): string
    {
        return __('enums.user_color.'.$this->value);
    }
}
