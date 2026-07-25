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
    <span v-if="props.message" :class="cn('-mt-1 text-sm font-medium text-red-600 dark:text-red-300', props.class)">
        {{ props.message }}
    </span>
</template>
