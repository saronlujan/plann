<script setup lang="ts">
import { trans } from 'laravel-vue-i18n';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Drawer,
    DrawerClose,
    DrawerContent,
    DrawerDescription,
    DrawerFooter,
    DrawerHeader,
    DrawerTitle,
} from '@/components/ui/drawer';
import { moneyLocale } from '@/lib/money';
import { formatCurrency } from '../format';
import type { TransactionSummary } from '../types';

/**
 * The full breakdown behind the month's headline figures.
 *
 * The card on the page shows what already happened; everything the projection
 * adds on top lives here, so the page stays a glance and this stays the study.
 */
const props = defineProps<{
    open: boolean;
    summary?: TransactionSummary | null;
    period: string;
}>();

const emit = defineEmits<{ 'update:open': [value: boolean] }>();

const drawerOpen = computed({
    get: () => props.open,
    set: (value: boolean) => emit('update:open', value),
});

// "2026-12" is the wire format; nobody reads a month that way.
const periodLabel = computed(() => {
    const [year, month] = props.period.split('-').map(Number);

    if (!year || !month) {
        return props.period;
    }

    return new Intl.DateTimeFormat(moneyLocale(), { month: 'long', year: 'numeric' }).format(
        new Date(year, month - 1, 1),
    );
});

type SummaryGroup = {
    title: string;
    rows: { label: string; value: string; emphasis?: boolean }[];
};

function money(amount: string): string {
    const summary = props.summary;

    if (!summary) {
        return '';
    }

    return formatCurrency(amount, summary.symbol, { code: summary.code });
}

// Realised first, projected second: one is history, the other is a forecast, and
// reading them as a single list of six invites mistaking one for the other.
const groups = computed<SummaryGroup[]>(() => {
    const summary = props.summary;

    if (!summary) {
        return [];
    }

    return [
        {
            title: trans('transactions.summary.realized_group'),
            rows: [
                { label: trans('transactions.summary.income'), value: money(summary.income) },
                { label: trans('transactions.summary.expenses'), value: money(summary.expenses) },
                {
                    label: trans('transactions.summary.total'),
                    value: money(summary.total),
                    emphasis: true,
                },
            ],
        },
        {
            title: trans('transactions.summary.expected_group'),
            rows: [
                {
                    label: trans('transactions.summary.expected_income'),
                    value: money(summary.expected_income),
                },
                {
                    label: trans('transactions.summary.expected_expense'),
                    value: money(summary.expected_expense),
                },
                {
                    label: trans('transactions.summary.expected_total'),
                    value: money(summary.expected_total),
                    emphasis: true,
                },
            ],
        },
    ];
});
</script>

<template>
    <!-- Bottom sheet with the drag handle: this is a quick look at a number, not
         a side panel to work in. -->
    <!-- handle-only: dragging from anywhere swallowed text selection. -->
    <Drawer v-model:open="drawerOpen" handle-only>
        <DrawerContent v-if="summary">
            <!--
                data-vaul-no-drag plus a stopped pointerdown: vaul captures the
                pointer on press anywhere in the sheet, which cancels the native
                text selection before a drag even starts. The handle above stays
                outside this subtree, so the sheet is still draggable.
            -->
            <!--
                touch-auto undoes the touch-action:none that vaul puts on every
                drawer, and select-text is stated outright rather than inherited:
                between the two the sheet reads like ordinary page text.
            -->
            <div
                data-vaul-no-drag
                class="mx-auto w-full max-w-md touch-auto select-text"
                @pointerdown.stop
            >
                <DrawerHeader class="gap-1 text-center sm:text-center">
                    <DrawerTitle class="text-xl">
                        {{ $t('transactions.summary.drawer_title') }}
                    </DrawerTitle>
                    <DrawerDescription>
                        {{ periodLabel }} · {{ summary.code }} — {{ summary.name }}
                    </DrawerDescription>
                </DrawerHeader>

                <div class="max-h-[60vh] space-y-6 overflow-y-auto px-4">
                    <section v-for="group in groups" :key="group.title" class="space-y-1">
                        <h3
                            class="text-center text-xs font-bold tracking-wide text-muted-foreground uppercase"
                        >
                            {{ group.title }}
                        </h3>
                        <dl class="divide-y">
                            <div
                                v-for="row in group.rows"
                                :key="row.label"
                                class="flex items-center justify-between gap-4 py-2 text-sm"
                            >
                                <dt class="text-muted-foreground">{{ row.label }}</dt>
                                <dd
                                    class="cursor-text whitespace-nowrap select-text"
                                    :class="row.emphasis ? 'font-semibold' : 'font-medium'"
                                >
                                    {{ row.value }}
                                </dd>
                            </div>
                        </dl>
                    </section>

                    <p class="text-center text-xs leading-relaxed text-muted-foreground">
                        {{ $t('transactions.summary.drawer_hint') }}
                    </p>
                </div>

                <DrawerFooter>
                    <DrawerClose as-child>
                        <Button variant="outline">{{ $t('common.actions.close') }}</Button>
                    </DrawerClose>
                </DrawerFooter>
            </div>
        </DrawerContent>
    </Drawer>
</template>
