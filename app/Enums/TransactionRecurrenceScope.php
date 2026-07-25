<?php

namespace App\Enums;

enum TransactionRecurrenceScope: string
{
    case All = 'all';

    case One = 'one';

    case Forward = 'forward';
}
