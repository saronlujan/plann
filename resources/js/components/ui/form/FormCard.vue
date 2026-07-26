<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        title?: string | null;
        subtitle?: string | null;
        class?: HTMLAttributes['class'];
    }>(),
    {
        title: null,
        subtitle: null,
    },
);
</script>

<template>
    <div :class="cn('flex flex-col gap-0 rounded-xl border border-border bg-card text-card-foreground', props.class)">
        <div v-if="props.title" class="border-b border-neutral-100 p-3 md:p-5 dark:border-neutral-900">
            <h2 class="font-semibold">
                {{ props.title }}
            </h2>
            <p v-if="props.subtitle" class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                {{ props.subtitle }}
            </p>
        </div>

        <div class="flex flex-col gap-5 p-3 md:p-5">
            <slot />
        </div>
    </div>
</template>
