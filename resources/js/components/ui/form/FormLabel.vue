<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        class?: HTMLAttributes['class'];
        message?: string | null;
    }>(),
    {
        message: null,
    },
);
</script>

<template>
    <label :class="cn('text-sm leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70', props.class)">
        <slot />
    </label>

    <p v-if="props.message" class="text-xs text-zinc-500 dark:text-zinc-400">
        {{ props.message }}
    </p>
</template>
