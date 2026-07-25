<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        label: string;
        disabled?: boolean;
        class?: HTMLAttributes['class'];
    }>(),
    {
        disabled: false,
    },
);
</script>

<template>
    <optgroup
        data-slot="native-select-optgroup"
        :label="props.label"
        :disabled="props.disabled"
        :class="cn(props.class)"
    >
        <slot />
    </optgroup>
</template>
