export type FilterOption = {
    label: string;
    value: string;
};

export type CurrencySummary = {
    code: string;
    name: string;
    symbol: string;
    entries: number;
    total: string;
};

export type TransactionEntry = {
    id: string;
    transaction_id: number;
    date: string;
    kind: 'unique' | 'base' | 'adjustment' | 'installment';
    type: 'unique' | 'recurring' | 'installment';
    schedule_type: 'unique' | 'recurring' | 'installment';
    movement_type: 'income' | 'expense';
    label: string;
    currency_code: string;
    currency_symbol: string;
    currency_id: number;
    account_id: number | null;
    effective_date: string;
    paid_at: string | null;
    effective_until: string | null;
    adjustment_month: string | null;
    amount: string;
    adjustment_amount: string;
    description: string;
    installment_frequency: 'weekly' | 'biweekly' | 'monthly' | null;
    installments_total: number | null;
    installment_number: number | null;
    source: string;
};

export type CurrencyOption = {
    id: number;
    code: string;
    name: string;
    symbol: string;
};

export type AccountOption = {
    id: number;
    name: string;
    currency_id: number;
};

export type Totals = {
    entries: number;
    unique: number;
    recurring: number;
    installment: number;
    adjustments: number;
};

export type TransactionFilters = {
    search: string;
    kind: string;
    order: string;
    date_from: string;
    date_to: string;
};

export type TransactionFormState = {
    movement_type: 'income' | 'expense';
    type: 'unique' | 'recurring' | 'installment';
    recurrence_scope: 'all' | 'one' | 'forward';
    occurrence_date: string;
    description: string;
    currency_id: string;
    account_id: string;
    effective_date: string;
    amount: string;
    adjustment_amount: string;
    adjustment_month: string;
    installment_frequency: 'weekly' | 'biweekly' | 'monthly';
    installments_total: string;
    installment_number: string;
};

export type TransactionPageProps = {
    period: string;
    periodLabel: string;
    periodDisplay: string;
    periodPrevious: string;
    periodNext: string;
    filters: TransactionFilters;
    kindOptions: FilterOption[];
    currencyOptions: CurrencyOption[];
    accountOptions: AccountOption[];
    currencySummaries: CurrencySummary[];
    entries: TransactionEntry[];
    totals: Totals;
};
