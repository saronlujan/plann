<?php

namespace App\Enums;

enum TransactionInstallmentFrequency: string
{
    case Weekly = 'weekly';

    case Biweekly = 'biweekly';

    case Monthly = 'monthly';

    case Bimonthly = 'bimonthly';

    case Semiannual = 'semiannual';

    case Annual = 'annual';

    public function label(): string
    {
        return match ($this) {
            self::Weekly => 'Semanal',
            self::Biweekly => 'Quinzenal',
            self::Monthly => 'Mensal',
            self::Bimonthly => 'Bimestral',
            self::Semiannual => 'Semestral',
            self::Annual => 'Anual',
        };
    }
}
