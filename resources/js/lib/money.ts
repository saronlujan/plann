import { getActiveLanguage } from 'laravel-vue-i18n';

// Currency formatting shared across the app. Intl handles per-currency decimals
// automatically (e.g. PYG has none), which keeps BRL/ARS/PYG consistent.
const LOCALE_TAG: Record<string, string> = {
    pt: 'pt-BR',
    en: 'en-US',
    es: 'es-AR',
};

/** The BCP 47 tag behind the active UI language, for any Intl formatter. */
export function moneyLocale(): string {
    return LOCALE_TAG[getActiveLanguage()] ?? 'pt-BR';
}

/**
 * Where daily practice has left ISO 4217 behind.
 *
 * The standard still gives the peso two decimals, but the centavo is out of
 * circulation — nobody types one. The guaraní needs no entry: ISO already says
 * zero, and Intl knows it.
 */
const FRACTION_DIGITS_OVERRIDE: Record<string, number> = {
    ARS: 0,
};

/** How many decimals a currency is written with, 0 for guaraní and peso. */
export function currencyFractionDigits(code: string): number {
    const override = FRACTION_DIGITS_OVERRIDE[code];

    if (override !== undefined) {
        return override;
    }

    try {
        return (
            new Intl.NumberFormat('en-US', { style: 'currency', currency: code }).resolvedOptions()
                .minimumFractionDigits ?? 2
        );
    } catch {
        // Non-ISO codes (USDT, anything a workspace invents) fall back to cents.
        return 2;
    }
}

export function formatMoney(value: string | number, currency: string): string {
    const amount = typeof value === 'number' ? value : Number.parseFloat(value);
    const safe = Number.isFinite(amount) ? amount : 0;
    const locale = moneyLocale();
    const digits = currencyFractionDigits(currency);

    try {
        return new Intl.NumberFormat(locale, {
            style: 'currency',
            currency,
            minimumFractionDigits: digits,
            maximumFractionDigits: digits,
        }).format(safe);
    } catch {
        // Non-ISO 4217 codes (e.g. USDT) throw — format the number and prefix the code.
        const number = new Intl.NumberFormat(locale, {
            minimumFractionDigits: digits,
            maximumFractionDigits: digits,
        }).format(safe);

        return `${currency} ${number}`;
    }
}
