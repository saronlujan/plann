<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        class?: HTMLAttributes['class'];
        modelValue?: string | number | null;
        type?: string;
    }>(),
    {
        type: 'text',
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

function handleInput(event: Event): void {
    const target = event.target as HTMLInputElement;

    emit('update:modelValue', target.value);
}
</script>

<template>
    <input
        data-slot="input"
        :type="props.type"
        :value="props.modelValue ?? ''"
        @input="handleInput"
        :class="cn(
            'border-input placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 flex h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none file:inline-flex file:border-0 file:bg-transparent file:text-sm file:font-medium md:text-sm',
            'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
            'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
            props.class,
        )"
    />
</template>
