<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';
import { activateLocale } from '@/lib/i18n';

const page = usePage<{ locale?: string; translations?: Record<string, string> }>();

watch(
    () => page.props.locale,
    (locale) => {
        const nextLocale = locale ?? 'en';

        if (typeof document !== 'undefined') {
            document.documentElement.lang = nextLocale.replace('_', '-');
        }

        if (import.meta.env.SSR) {
            return;
        }

        // Server-provided messages for the active locale — always fresh.
        void activateLocale(nextLocale, page.props.translations ?? {});
    },
    { immediate: true },
);
</script>

<template>
    <span class="sr-only" aria-hidden="true"></span>
</template>
