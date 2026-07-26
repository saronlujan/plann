<?php

namespace App\Enums;

enum AccountKind: string
{
    case Account = 'account';

    case Bank = 'bank';

    case Wallet = 'wallet';

    case CreditCard = 'credit_card';

    public function label(): string
    {
        return __('enums.account_kind.'.$this->value);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $kind): array => ['value' => $kind->value, 'label' => $kind->label()],
            self::cases(),
        );
    }
}
