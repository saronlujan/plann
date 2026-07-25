<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { PencilIcon, ThumbsUpIcon, Trash2Icon } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableFooter,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import DefaultLayout from '@/layouts/DefaultLayout.vue';
import transactions from '@/routes/transactions';
import TransactionModal from './components/TransactionModal.vue';
import { formatCurrency } from './format';
import type {
    AccountOption,
    CurrencyOption,
    Option,
    TransactionEntry,
    TransactionSummary,
} from './types';

const props = defineProps<{
    movementTypeOptions: Option[];
    scheduleTypeOptions: Option[];
    frequencyOptions: Option[];
    currencyOptions: CurrencyOption[];
    accountOptions: AccountOption[];
    entries: TransactionEntry[];
    summaries: TransactionSummary[];
}>();

// Movement colors are financial data encoding (in = green, out = red),
// intentionally decoupled from the configurable brand primary.
const movementBadge: Record<string, { label: string; class: string }> = {
    expense: {
        label: 'Despesa',
        class: 'rounded-full border-red-200 bg-red-50 text-red-600 dark:border-red-900 dark:bg-red-950 dark:text-red-400',
    },
    income: {
        label: 'Receita',
        class: 'rounded-full border-emerald-200 bg-emerald-50 text-emerald-600 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-400',
    },
    transfer: {
        label: 'Transferência',
        class: 'rounded-full border-sky-200 bg-sky-50 text-sky-600 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-400',
    },
};

const showCurrencyCode = computed(() => props.summaries.length > 1);

function summaryRows(summary: TransactionSummary): { label: string; value: string }[] {
    return [
        { label: 'Receita', value: formatCurrency(summary.income, summary.symbol) },
        { label: 'Despesas', value: formatCurrency(summary.expenses, summary.symbol) },
        { label: 'Total', value: formatCurrency(summary.total, summary.symbol) },
        { label: 'Receita prevista', value: formatCurrency(summary.expected_income, summary.symbol) },
        { label: 'Despesa prevista', value: formatCurrency(summary.expected_expense, summary.symbol) },
        { label: 'Total previsto', value: formatCurrency(summary.expected_total, summary.symbol) },
    ];
}

const modalOpen = ref(false);
const editingEntry = ref<TransactionEntry | null>(null);

function openCreate(): void {
    editingEntry.value = null;
    modalOpen.value = true;
}

function openEdit(entry: TransactionEntry): void {
    editingEntry.value = entry;
    modalOpen.value = true;
}

function togglePaid(entry: TransactionEntry): void {
    router.patch(transactions.pay(entry.transaction_id).url, {}, { preserveScroll: true });
}

function deleteEntry(entry: TransactionEntry): void {
    if (!window.confirm('Excluir esta transação?')) {
        return;
    }

    router.delete(transactions.destroy(entry.transaction_id).url, { preserveScroll: true });
}

function signedAmount(entry: TransactionEntry): number {
    const value = Number.parseFloat(entry.amount);

    if (entry.movement_type === 'income' || entry.movement_type === 'transfer') {
        return value;
    }

    return -value;
}

const hasEntries = computed(() => props.entries.length > 0);
</script>

<template>
    <Head title="Transações" />

    <DefaultLayout>
        <main class="flex flex-col gap-5 p-3 md:p-5">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-col">
                    <h1 class="text-lg font-semibold md:text-xl">Suas transações</h1>
                    <span class="text-sm text-muted-foreground">
                        Visualize e gerencie seus lançamentos.
                    </span>
                </div>

                <Button class="shrink-0" @click="openCreate">Adicionar transação</Button>
            </div>

            <Card>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Tipo</TableHead>
                                <TableHead>Descrição</TableHead>
                                <TableHead></TableHead>
                                <TableHead class="text-right">Valor</TableHead>
                                <TableHead></TableHead>
                                <TableHead></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableEmpty v-if="!hasEntries" :colspan="6">
                                Nenhuma transação cadastrada.
                            </TableEmpty>
                            <TableRow v-for="entry in entries" :key="entry.id">
                                <TableCell>
                                    <Badge variant="outline" :class="movementBadge[entry.movement_type]?.class">
                                        {{ movementBadge[entry.movement_type]?.label ?? entry.movement_type }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div class="flex flex-col">
                                        <span class="font-medium">{{ entry.label }}</span>
                                        <span class="text-xs text-muted-foreground">{{ entry.source }}</span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge v-if="entry.paid_at">Pago</Badge>
                                </TableCell>
                                <TableCell class="text-right font-medium whitespace-nowrap">
                                    {{ formatCurrency(signedAmount(entry), entry.currency_symbol, { signed: true }) }}
                                </TableCell>
                                <TableCell class="whitespace-nowrap text-sm text-muted-foreground">
                                    {{ entry.date }}
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="icon"
                                            :aria-label="entry.paid_at ? 'Marcar como não pago' : 'Marcar como pago'"
                                            :class="entry.paid_at ? 'text-emerald-600 dark:text-emerald-400' : ''"
                                            @click="togglePaid(entry)"
                                        >
                                            <ThumbsUpIcon class="size-4" aria-hidden="true" />
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="icon"
                                            aria-label="Editar transação"
                                            @click="openEdit(entry)"
                                        >
                                            <PencilIcon class="size-4" aria-hidden="true" />
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="icon"
                                            aria-label="Excluir transação"
                                            @click="deleteEntry(entry)"
                                        >
                                            <Trash2Icon class="size-4" aria-hidden="true" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                        <TableFooter v-if="summaries.length">
                            <template v-for="summary in summaries" :key="summary.code">
                                <TableRow v-for="row in summaryRows(summary)" :key="`${summary.code}-${row.label}`">
                                    <TableCell :colspan="5" class="text-right font-medium">
                                        <span v-if="showCurrencyCode" class="text-muted-foreground">{{ summary.code }} · </span>
                                        {{ row.label }}:
                                    </TableCell>
                                    <TableCell class="text-right whitespace-nowrap">{{ row.value }}</TableCell>
                                </TableRow>
                            </template>
                        </TableFooter>
                    </Table>
                </CardContent>
            </Card>

            <TransactionModal
                v-model:open="modalOpen"
                :entry="editingEntry"
                :currency-options="currencyOptions"
                :account-options="accountOptions"
                :movement-type-options="movementTypeOptions"
                :schedule-type-options="scheduleTypeOptions"
                :frequency-options="frequencyOptions"
            />
        </main>
    </DefaultLayout>
</template>
