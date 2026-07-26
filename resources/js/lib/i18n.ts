import { loadLanguageAsync } from 'laravel-vue-i18n';

/**
 * Translations are delivered by the backend via Inertia shared props on every
 * request, so they are always fresh and never depend on build-time generated
 * files (which is what used to make the language "break until a rebuild").
 */
type Messages = Record<string, string>;

const store: Record<string, Messages> = {};

export function setLocaleMessages(locale: string, messages: Messages): void {
    store[locale] = messages;
}

// Return the RAW messages object. laravel-vue-i18n's `avoidException` does not
// unwrap `.default` on the synchronous path, so wrapping it breaks the lookup.
export function resolveLocaleMessages(lang: string): Messages {
    return store[lang] ?? {};
}

export async function activateLocale(locale: string, messages: Messages): Promise<void> {
    setLocaleMessages(locale, messages);
    await loadLanguageAsync(locale);
}
