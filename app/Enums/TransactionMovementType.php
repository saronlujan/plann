<?php

namespace App\Enums;

enum TransactionMovementType: string
{
    case Income = 'income';

    case Expense = 'expense';

    case Transfer = 'transfer';

    public function label(): string
    {
        return match ($this) {
            self::Income => 'Receita',
            self::Expense => 'Despesa',
            self::Transfer => 'Transferência',
        };
    }
}
