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
        return __('enums.transaction_installment_frequency.'.$this->value);
    }
}
