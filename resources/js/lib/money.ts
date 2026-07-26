import { getActiveLanguage } from 'laravel-vue-i18n';

// Currency formatting shared across the app. Intl handles per-currency decimals
// automatically (e.g. PYG has none), which keeps BRL/ARS/PYG consistent.
const LOCALE_TAG: Record<string, string> = {
    pt: 'pt-BR',
    en: 'en-US',
    es: 'es-AR',
};

export function formatMoney(value: string | number, currency: string): string {
    const amount = typeof value === 'number' ? value : Number.parseFloat(value);
    const safe = Number.isFinite(amount) ? amount : 0;
    const locale = LOCALE_TAG[getActiveLanguage()] ?? 'pt-BR';

    try {
        return new Intl.NumberFormat(locale, { style: 'currency', currency }).format(safe);
    } catch {
        // Non-ISO 4217 codes (e.g. USDT) throw — format the number and prefix the code.
        const number = new Intl.NumberFormat(locale, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(safe);

        return `${currency} ${number}`;
    }
}
