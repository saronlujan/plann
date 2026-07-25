import '../css/app.css';
import { createInertiaApp } from '@inertiajs/vue3';
import { i18nVue } from 'laravel-vue-i18n';
import { createApp, createSSRApp, h, type DefineComponent } from 'vue';
import LocaleSync from '@/components/layout/AppLocaleSync.vue';
import { Toaster } from '@/components/ui/sonner';
import 'vue-sonner/style.css';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const pages = import.meta.glob<{ default: DefineComponent }>('./pages/**/*.vue');
const langFiles = import.meta.glob('../../lang/php_*.json');
const langFilesEager = import.meta.glob('../../lang/php_*.json', { eager: true });

function resolveLocaleFile(lang: string) {
    if (import.meta.env.SSR) {
        return (
            langFilesEager[`../../lang/php_${lang}.json`] as
                | { default: Record<string, string> }
                | undefined
        )?.default ?? {};
    }

    const loader = langFiles[`../../lang/php_${lang}.json`];

    if (!loader) {
        return Promise.resolve({ default: {} });
    }

    return loader();
}

function getInitialLocale(initialLocale?: string): string {
    if (initialLocale) {
        return initialLocale.replace('-', '_');
    }

    if (typeof document === 'undefined') {
        return 'en';
    }

    return document.documentElement.lang?.replace('-', '_') || 'en';
}

createInertiaApp({
    resolve: (name) => {
        const page = pages[`./pages/${name}.vue`];

        if (!page) {
            throw new Error(`Unable to resolve page: ${name}`);
        }

        return page().then((module) => module.default);
    },
    setup({ el, App, props, plugin }) {
        const initialLocale = getInitialLocale(
            (props.initialPage.props as { locale?: string }).locale,
        );
        const vueApp = import.meta.env.SSR
            ? createSSRApp({
                  render: () => h('div', [h(App, props), h(LocaleSync)]),
              })
            : createApp({
                  render: () => h('div', [h(App, props), h(LocaleSync), h(Toaster)]),
              });

        vueApp.use(plugin);
        vueApp.use(i18nVue, {
            lang: initialLocale,
            fallbackLang: 'en',
            resolve: resolveLocaleFile,
        });

        if (!import.meta.env.SSR && el) {
            vueApp.mount(el);
        }

        return vueApp;
    },
    title: (title) => (title ? `${title} - ${appName}` : appName),
    progress: {
        color: '#4B5563',
    },
});
