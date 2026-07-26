<?php

namespace App\Enums;

enum CategoryType: string
{
    case Income = 'income';

    case Expense = 'expense';

    case Both = 'both';

    public function label(): string
    {
        return __('enums.category_type.'.$this->value);
    }
}
