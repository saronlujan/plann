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
}
