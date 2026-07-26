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
        return match ($this) {
            self::Blue => 'Azul',
            self::Indigo => 'Índigo',
            self::Purple => 'Roxo',
            self::Pink => 'Rosa',
            self::Cyan => 'Ciano',
            self::Orange => 'Laranja',
            self::Yellow => 'Amarelo',
            self::Green => 'Verde',
            self::Teal => 'Turquesa',
            self::Zinc => 'Zinc',
        };
    }
}
