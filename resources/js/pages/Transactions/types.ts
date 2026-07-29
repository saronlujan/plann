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

export type CategoryOption = {
    id: number;
    name: string;
    type: string;
    color: string;
};

export type TagOption = {
    id: number;
    name: string;
    color: string;
};

export type ContactOption = {
    id: number;
    name: string;
    type: string;
};

export type ServiceOption = {
    id: number;
    name: string;
    /** Suggested when the service is appended; the line then holds its own value. */
    default_price: string | null;
    currency_id: number | null;
    color: string;
};

/**
 * One part of what a transaction was made of. A null service is a line whose
 * service has been retired: it still carries money, so it is shown and sent back
 * rather than dropped.
 */
export type ServiceLine = {
    service_id: number | null;
    amount: string;
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
    category_id: number | null;
    contact_id: number | null;
    services: ServiceLine[];
    tag_ids: number[];
    date: string;
    kind: 'unique' | 'base' | 'adjustment' | 'installment';
    type: string;
    schedule_type: string;
    movement_type: 'income' | 'expense' | 'transfer';
    is_transfer: boolean;
    /** Stored file name, or null. The URL comes from the download route. */
    attachment: string | null;
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
    note: string | null;
    observations: string | null;
    installment_frequency: string | null;
    installments_total: number | null;
    installment_number: number | null;
    source: string;
    account_kind: string | null;
    transfer_from: string | null;
    transfer_to: string | null;
};
