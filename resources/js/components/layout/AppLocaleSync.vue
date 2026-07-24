<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';

type Locale = 'pt' | 'es' | 'en';

const page = usePage<{ locale?: Locale }>();

function syncLocale(locale?: Locale): void {
    const nextLocale = locale ?? 'en';

    if (import.meta.env.SSR) {
        return;
    }

    if (typeof document !== 'undefined') {
        document.documentElement.lang = nextLocale.replace('_', '-');
    }
}

watch(
    () => page.props.locale,
    (locale) => {
        syncLocale(locale);
    },
    { immediate: true },
);
</script>

<template>
    <span class="sr-only" aria-hidden="true"></span>
</template>
