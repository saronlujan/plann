<?php

namespace App\Enums;

enum TransactionType: string
{
    case Unique = 'unique';

    case Recurring = 'recurring';

    case Installment = 'installment';
}
