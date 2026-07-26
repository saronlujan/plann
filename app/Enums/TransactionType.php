<?php

namespace App\Enums;

enum TransactionType: string
{
    case Unique = 'unique';

    case Recurring = 'recurring';

    case Installment = 'installment';

    public function label(): string
    {
        return __('enums.transaction_type.'.$this->value);
    }
}
