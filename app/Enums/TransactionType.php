<?php

namespace App\Enums;

enum TransactionType: string
{
    case Unique = 'unique';

    case Recurring = 'recurring';

    case Installment = 'installment';

    public function label(): string
    {
        return match ($this) {
            self::Unique => 'Única',
            self::Recurring => 'Recorrente',
            self::Installment => 'Parcelada',
        };
    }
}
