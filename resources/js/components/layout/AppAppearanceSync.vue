<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';
import { applyAppearance } from '@/composables/useAppearance';
import type { ColorValue, ThemeValue } from '@/composables/useAppearance';

const page = usePage<{ auth?: { user?: { theme?: ThemeValue; color?: ColorValue } | null } }>();

watch(
    () => [page.props.auth?.user?.theme, page.props.auth?.user?.color] as const,
    ([theme, color]) => {
        if (import.meta.env.SSR) {
            return;
        }

        applyAppearance(theme ?? 'light', color ?? 'zinc');
    },
    { immediate: true },
);
</script>

<template>
    <span class="sr-only" aria-hidden="true"></span>
</template>
