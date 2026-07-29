<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    class?: HTMLAttributes['class'];
    modelValue?: string | null;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

function handleInput(event: Event): void {
    emit('update:modelValue', (event.target as HTMLTextAreaElement).value);
}
</script>

<template>
    <textarea
        data-slot="textarea"
        :value="props.modelValue ?? ''"
        @input="handleInput"
        :class="cn(
            'border-input placeholder:text-muted-foreground dark:bg-input/30 flex field-sizing-content min-h-16 w-full rounded-md border bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none md:text-sm',
            'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
            'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
            props.class,
        )"
    />
</template>
