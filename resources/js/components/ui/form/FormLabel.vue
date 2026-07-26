<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        class?: HTMLAttributes['class'];
        message?: string | null;
        for?: string;
    }>(),
    {
        message: null,
        for: undefined,
    },
);
</script>

<template>
    <label
        :for="props.for"
        :class="cn('text-sm leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70', props.class)"
    >
        <slot />
    </label>

    <p v-if="props.message" class="text-xs text-muted-foreground">
        {{ props.message }}
    </p>
</template>
