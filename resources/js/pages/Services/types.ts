export type Service = {
    id: number;
    name: string;
    default_price: string | null;
    currency_id: number | null;
    color: string;
};

export type CurrencyOption = {
    value: string;
    label: string;
    symbol: string;
    code: string;
};
