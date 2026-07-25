<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        value: string | number;
        disabled?: boolean;
        class?: HTMLAttributes['class'];
    }>(),
    {
        disabled: false,
    },
);
</script>

<template>
    <option
        data-slot="native-select-option"
        :value="props.value"
        :disabled="props.disabled"
        :class="cn(props.class)"
    >
        <slot />
    </option>
</template>
