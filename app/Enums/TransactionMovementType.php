<?php

namespace App\Enums;

enum TransactionMovementType: string
{
    case Income = 'income';

    case Expense = 'expense';

    case Transfer = 'transfer';
}
