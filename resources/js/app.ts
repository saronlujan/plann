import '../css/app.css';
import { createInertiaApp } from '@inertiajs/vue3';
import { i18nVue } from 'laravel-vue-i18n';
import { createApp, createSSRApp, h } from 'vue';
import type { DefineComponent } from 'vue';
import AppearanceSync from '@/components/layout/AppAppearanceSync.vue';
import LocaleSync from '@/components/layout/AppLocaleSync.vue';
import { Toaster } from '@/components/ui/sonner';
import { resolveLocaleMessages, setLocaleMessages } from '@/lib/i18n';
import { registerServiceWorker } from '@/lib/pwa';
import 'vue-sonner/style.css';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const pages = import.meta.glob<{ default: DefineComponent }>('./pages/**/*.vue');

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
        const initialProps = props.initialPage.props as {
            locale?: string;
            translations?: Record<string, string>;
        };
        const initialLocale = getInitialLocale(initialProps.locale);

        // Seed the active locale's messages (delivered by the server) before the
        // i18n plugin resolves them.
        setLocaleMessages(initialLocale, initialProps.translations ?? {});

        const vueApp = import.meta.env.SSR
            ? createSSRApp({
                  render: () => h('div', [h(App, props), h(LocaleSync), h(AppearanceSync)]),
              })
            : createApp({
                  render: () =>
                      h('div', [
                          h(App, props),
                          h(LocaleSync),
                          h(AppearanceSync),
                          h(Toaster, { position: 'bottom-center' }),
                      ]),
              });

        vueApp.use(plugin);
        vueApp.use(i18nVue, {
            lang: initialLocale,
            fallbackLang: 'en',
            resolve: resolveLocaleMessages,
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

registerServiceWorker();
