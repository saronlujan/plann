import { currencyFractionDigits } from '@/lib/money';

/**
 * Format a decimal string/number as a currency amount using the current locale.
 *
 * Amounts arrive from the backend as plain decimal strings (e.g. "-1700.00")
 * to avoid floating-point drift, so parsing happens here at the presentation edge.
 * Set `signed` to prefix positive values with "+" (used in the amount column).
 * `code` decides the decimals — guaraní and peso are written without them.
 */
export function formatCurrency(
    amount: string | number,
    symbol: string,
    options: { signed?: boolean; locale?: string; code?: string } = {},
): string {
    const { signed = false, locale = 'pt-BR', code } = options;
    const digits = code ? currencyFractionDigits(code) : 2;
    const value = typeof amount === 'number' ? amount : Number.parseFloat(amount);
    const safeValue = Number.isFinite(value) ? value : 0;

    const formatted = new Intl.NumberFormat(locale, {
        minimumFractionDigits: digits,
        maximumFractionDigits: digits,
    }).format(Math.abs(safeValue));

    const sign = safeValue < 0 ? '-' : signed && safeValue > 0 ? '+' : '';

    return `${sign}${symbol} ${formatted}`;
}

/**
 * Format an ISO date (YYYY-MM-DD) for display without timezone shifting.
 */
export function formatDate(date: string, locale = 'pt-BR'): string {
    const [year, month, day] = date.split('-').map(Number);

    if (!year || !month || !day) {
        return date;
    }

    return new Intl.DateTimeFormat(locale, {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    }).format(new Date(year, month - 1, day));
}
