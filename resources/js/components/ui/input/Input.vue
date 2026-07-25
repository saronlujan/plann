<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        class?: HTMLAttributes['class'];
        modelValue?: string | number | File | null;
        type?: string;
    }>(),
    {
        type: 'text',
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string | File | null];
}>();

function handleInput(event: Event): void {
    if (props.type === 'file') {
        return;
    }

    const target = event.target as HTMLInputElement;

    emit('update:modelValue', target.value);
}

function handleChange(event: Event): void {
    if (props.type !== 'file') {
        return;
    }

    const target = event.target as HTMLInputElement;

    emit('update:modelValue', target.files?.[0] ?? null);
}
</script>

<template>
    <input
        data-slot="input"
        :type="props.type"
        :value="props.type === 'file' ? undefined : (props.modelValue ?? '')"
        @input="handleInput"
        @change="handleChange"
        :class="cn(
            'border-input placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 flex h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none file:inline-flex file:border-0 file:bg-transparent file:text-sm file:font-medium md:text-sm',
            'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
            'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
            props.class,
        )"
    />
</template>
