export type Option = {
    value: string;
    label: string;
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

export type CurrencySummary = {
    code: string;
    name: string;
    symbol: string;
    entries: number;
    total: string;
};

export type TransactionFilters = {
    search: string;
    kind: string;
    order: string;
    date_from: string;
    date_to: string;
};

export type TransactionSummary = {
    code: string;
    name: string;
    symbol: string;
    income: string;
    expenses: string;
    total: string;
    expected_income: string;
    expected_expense: string;
    expected_total: string;
};

export type TransactionEntry = {
    id: string;
    transaction_id: number;
    date: string;
    kind: 'unique' | 'base' | 'adjustment' | 'installment';
    type: string;
    schedule_type: string;
    movement_type: 'income' | 'expense' | 'transfer';
    label: string;
    currency_code: string;
    currency_symbol: string;
    currency_id: number;
    account_id: number | null;
    effective_date: string;
    paid_at: string | null;
    effective_until?: string | null;
    adjustment_month: string | null;
    amount: string;
    adjustment_amount: string;
    description: string;
    installment_frequency: string | null;
    installments_total: number | null;
    installment_number: number | null;
    source: string;
};
