<script setup lang="ts">
import { ChevronUp } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { cn } from '@/lib/utils';
import { formatCurrency } from '../format';
import type { TransactionSummary } from '../types';

/**
 * The month's bottom line, pinned to the viewport.
 *
 * Totals are what people check while scrolling a long list, so they follow the
 * scroll instead of waiting at the end of it.
 */
const props = defineProps<{
    summaries: TransactionSummary[];
}>();

const emit = defineEmits<{ details: [summary: TransactionSummary] }>();

const activeCode = ref(props.summaries[0]?.code ?? '');

// A currency can disappear when the month changes; fall back rather than show
// an empty bar.
watch(
    () => props.summaries,
    (summaries) => {
        if (!summaries.some((summary) => summary.code === activeCode.value)) {
            activeCode.value = summaries[0]?.code ?? '';
        }
    },
);

const active = computed(
    () => props.summaries.find((summary) => summary.code === activeCode.value) ?? null,
);

const rows = computed(() => {
    const summary = active.value;

    if (!summary) {
        return [];
    }

    const money = (amount: string): string =>
        formatCurrency(amount, summary.symbol, { code: summary.code });

    // Same encoding the list uses: in is green, out is red, decoupled from the
    // configurable brand colour. The net total stays neutral — its meaning comes
    // from the sign, not from a colour.
    return [
        {
            key: 'income',
            label: 'transactions.summary.income',
            value: money(summary.income),
            class: 'text-emerald-600 dark:text-emerald-400',
        },
        {
            key: 'expenses',
            label: 'transactions.summary.expenses',
            value: money(summary.expenses),
            class: 'text-red-600 dark:text-red-400',
        },
        {
            key: 'total',
            label: 'transactions.summary.total',
            value: money(summary.total),
            class: '',
        },
    ];
});
</script>

<template>
    <div
        v-if="active"
        class="fixed inset-x-0 bottom-0 z-40 border-t bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/80"
    >
        <!--
            The whole bar is the control, so it is the element carrying the role
            and the keyboard handling. Nothing interactive may nest inside it —
            the currency pills stop their own clicks, and the chevron is drawn as
            decoration.
        -->
        <div
            role="button"
            tabindex="0"
            :aria-label="$t('transactions.summary.see_details')"
            class="relative flex cursor-pointer items-center gap-3 p-3 transition-colors hover:bg-muted/50 focus-visible:bg-muted/50 focus-visible:outline-none md:gap-5 md:px-5"
            @click="emit('details', active)"
            @keydown.enter.prevent="emit('details', active)"
            @keydown.space.prevent="emit('details', active)"
        >
            <!--
                One currency needs no switcher; several do, and the bar is the
                only place they are visible at a glance. Styled as tabs, not
                tags: this picks which currency the bar is showing, it does not
                label it.
            -->
            <div
                v-if="summaries.length > 1"
                role="tablist"
                class="relative z-10 inline-flex h-8 w-fit shrink-0 items-center justify-center rounded-lg bg-muted p-[3px] text-muted-foreground"
            >
                <button
                    v-for="summary in summaries"
                    :key="summary.code"
                    type="button"
                    role="tab"
                    :aria-selected="summary.code === activeCode"
                    :class="
                        cn(
                            'inline-flex h-[calc(100%-1px)] items-center justify-center rounded-md border border-transparent px-2 py-1 text-xs font-medium whitespace-nowrap transition-[color,box-shadow]',
                            summary.code === activeCode
                                ? 'bg-background text-foreground shadow-sm dark:border-input dark:bg-input/30'
                                : 'hover:text-foreground',
                        )
                    "
                    @click.stop="activeCode = summary.code"
                >
                    {{ summary.code }}
                </button>
            </div>

            <!--
                Absolutely centred like the header nav: in the flow, the currency
                pills on one side and the chevron on the other push the totals off
                centre, and by a different amount depending on how many currencies
                the workspace has. z-0 keeps them under the pills and the chevron,
                which stay clickable.
            -->
            <dl
                class="pointer-events-none absolute inset-x-0 z-0 m-auto flex flex-wrap items-center justify-center gap-x-5 gap-y-1 text-sm"
            >
                <div v-for="row in rows" :key="row.key" class="flex items-center gap-1.5">
                    <dt class="text-muted-foreground">{{ $t(row.label) }}:</dt>
                    <dd :class="cn('font-semibold whitespace-nowrap', row.class)">
                        {{ row.value }}
                    </dd>
                </div>
            </dl>

            <!--
                A chevron, not a plus: this expands the bar into the drawer, and a
                filled + beside the one that creates a transaction read as a second
                way to add one.
            -->
            <span
                class="relative z-10 ml-auto shrink-0 p-2 text-zinc-500 dark:text-zinc-400"
                aria-hidden="true"
            >
                <ChevronUp class="size-5" />
            </span>
        </div>
    </div>
</template>
