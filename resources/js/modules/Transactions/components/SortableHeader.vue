<script setup lang="ts">
import { ArrowDownIcon, ArrowUpIcon, ChevronsUpDownIcon } from '@lucide/vue';
import { computed } from 'vue';
import { cn } from '@/lib/utils';

/**
 * A column heading that sorts. Pressing it applies the first direction; pressing
 * it again turns it around.
 */
const props = defineProps<{
    label: string;
    /** The two order names this column owns, in the order pressing cycles them. */
    orders: [string, string];
    current: string;
    align?: 'left' | 'right';
}>();

const emit = defineEmits<{ sort: [order: string] }>();

const index = computed(() => props.orders.indexOf(props.current));
const isActive = computed(() => index.value !== -1);

/** Whichever direction is not in force, so a press always changes something. */
const nextOrder = computed(() =>
    index.value === 0 ? props.orders[1] : props.orders[0],
);
</script>

<template>
    <button
        type="button"
        :class="
            cn(
                'inline-flex items-center gap-1 transition hover:text-foreground',
                isActive ? 'font-medium text-foreground' : 'text-muted-foreground',
                align === 'right' && 'flex-row-reverse',
            )
        "
        :aria-label="label"
        @click="emit('sort', nextOrder)"
    >
        {{ label }}
        <ArrowDownIcon v-if="index === 0" class="size-3.5" aria-hidden="true" />
        <ArrowUpIcon v-else-if="index === 1" class="size-3.5" aria-hidden="true" />
        <!-- Shown faintly on the idle columns so it reads as "this one sorts too". -->
        <ChevronsUpDownIcon v-else class="size-3.5 opacity-40" aria-hidden="true" />
    </button>
</template>
