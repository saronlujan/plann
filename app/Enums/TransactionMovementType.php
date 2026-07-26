<?php

namespace App\Enums;

enum TransactionMovementType: string
{
    case Income = 'income';

    case Expense = 'expense';

    case Transfer = 'transfer';

    public function label(): string
    {
        return __('enums.transaction_movement_type.'.$this->value);
    }
}
