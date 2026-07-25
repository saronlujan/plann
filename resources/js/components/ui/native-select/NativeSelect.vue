<script setup lang="ts">
import { ChevronDownIcon } from '@lucide/vue';
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

const model = defineModel<string | number | null>({ default: null });

const props = withDefaults(
    defineProps<{
        class?: HTMLAttributes['class'];
    }>(),
    {},
);

const emit = defineEmits<{
    change: [event: Event];
}>();

function handleChange(event: Event): void {
    const target = event.target as HTMLSelectElement;

    model.value = target.value;
    emit('change', event);
}
</script>

<template>
    <div class="relative w-full">
        <select
            data-slot="native-select"
            v-model="model"
            v-bind="$attrs"
            :class="cn(
                'border-input placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 flex h-9 w-full min-w-0 appearance-none rounded-md border bg-background px-3 py-1 pr-8 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive disabled:cursor-not-allowed disabled:opacity-50',
                props.class,
            )"
            @change="handleChange"
        >
            <slot />
        </select>

        <ChevronDownIcon
            aria-hidden="true"
            class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-zinc-500"
        />
    </div>
</template>
