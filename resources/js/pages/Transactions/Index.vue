<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronLeftIcon, ChevronRightIcon, PencilIcon, CheckIcon } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import DefaultLayout from '@/layouts/DefaultLayout.vue';
import transactions from '@/routes/transactions';
import TransactionModal from './components/TransactionModal.vue';
import { formatCurrency, formatDate } from './format';
import type {
    AccountOption,
    CurrencyOption,
    CurrencySummary,
    Option,
    TransactionEntry,
    TransactionFilters,
    TransactionTotals,
} from './types';

const props = defineProps<{
    period: string;
    periodLabel: string;
    periodDisplay: string;
    periodPrevious: string;
    periodNext: string;
    filters: TransactionFilters;
    kindOptions: Option[];
    movementTypeOptions: Option[];
    scheduleTypeOptions: Option[];
    frequencyOptions: Option[];
    currencyOptions: CurrencyOption[];
    accountOptions: AccountOption[];
    currencySummaries: CurrencySummary[];
    entries: TransactionEntry[];
    totals: TransactionTotals;
}>();

const orderOptions: Option[] = [
    { value: 'recent', label: 'Mais recentes' },
    { value: 'oldest', label: 'Mais antigas' },
];

const kindStyles: Record<string, { label: string; class: string }> = {
    unique: { label: 'Única', class: 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' },
    base: { label: 'Recorrência', class: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' },
    adjustment: { label: 'Ajuste', class: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300' },
    installment: { label: 'Parcela', class: 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300' },
};

const search = ref(props.filters.search);
const kind = ref(props.filters.kind);
const order = ref(props.filters.order);

const modalOpen = ref(false);
const editingEntry = ref<TransactionEntry | null>(null);

let searchTimeout: ReturnType<typeof setTimeout> | undefined;

function applyFilters(): void {
    router.get(
        transactions.index().url,
        {
            period: props.period,
            date_from: props.filters.date_from,
            date_to: props.filters.date_to,
            search: search.value,
            kind: kind.value,
            order: order.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 350);
});

watch([kind, order], applyFilters);

function openCreate(): void {
    editingEntry.value = null;
    modalOpen.value = true;
}

function openEdit(entry: TransactionEntry): void {
    editingEntry.value = entry;
    modalOpen.value = true;
}

function payEntry(entry: TransactionEntry): void {
    router.patch(
        transactions.pay(entry.transaction_id).url,
        {},
        { preserveScroll: true },
    );
}

function signedAmount(entry: TransactionEntry): number {
    const value = Number.parseFloat(entry.amount);

    if (entry.movement_type === 'income' || entry.movement_type === 'transfer') {
        return value;
    }

    return -value;
}

function amountClass(entry: TransactionEntry): string {
    if (entry.movement_type === 'income') {
        return 'text-emerald-600 dark:text-emerald-400';
    }

    if (entry.movement_type === 'transfer') {
        return 'text-sky-600 dark:text-sky-400';
    }

    return 'text-red-600 dark:text-red-400';
}

function summaryClass(total: string): string {
    return Number.parseFloat(total) < 0
        ? 'text-red-600 dark:text-red-400'
        : 'text-emerald-600 dark:text-emerald-400';
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
                    <span class="text-sm text-zinc-400 dark:text-zinc-500">
                        Visualize e gerencie seus lançamentos por mês.
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="periodPrevious"
                        aria-label="Mês anterior"
                        class="inline-flex size-9 items-center justify-center rounded-md border border-input transition hover:bg-accent"
                    >
                        <ChevronLeftIcon class="size-4" aria-hidden="true" />
                    </Link>
                    <span class="min-w-36 text-center text-sm font-medium capitalize">
                        {{ periodDisplay }}
                    </span>
                    <Link
                        :href="periodNext"
                        aria-label="Próximo mês"
                        class="inline-flex size-9 items-center justify-center rounded-md border border-input transition hover:bg-accent"
                    >
                        <ChevronRightIcon class="size-4" aria-hidden="true" />
                    </Link>

                    <Button class="ml-2 shrink-0" @click="openCreate">Adicionar transação</Button>
                </div>
            </div>

            <div v-if="currencySummaries.length" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <Card v-for="summary in currencySummaries" :key="summary.code">
                    <CardContent class="flex items-center justify-between gap-3 p-4">
                        <div class="flex flex-col">
                            <CardTitle class="text-sm">{{ summary.code }}</CardTitle>
                            <CardDescription class="text-xs">
                                {{ summary.entries }} lançamento(s)
                            </CardDescription>
                        </div>
                        <span class="text-base font-semibold" :class="summaryClass(summary.total)">
                            {{ formatCurrency(summary.total, summary.symbol) }}
                        </span>
                    </CardContent>
                </Card>
            </div>

            <div class="flex flex-wrap items-end gap-3">
                <div class="flex flex-1 flex-col gap-1">
                    <label for="filter-search" class="text-xs text-zinc-500">Buscar</label>
                    <Input
                        id="filter-search"
                        v-model="search"
                        type="search"
                        name="search"
                        placeholder="Descrição, origem ou moeda"
                    />
                </div>
                <div class="flex flex-col gap-1">
                    <label for="filter-kind" class="text-xs text-zinc-500">Tipo</label>
                    <NativeSelect id="filter-kind" v-model="kind" name="kind" class="w-44">
                        <NativeSelectOption
                            v-for="option in kindOptions"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </NativeSelectOption>
                    </NativeSelect>
                </div>
                <div class="flex flex-col gap-1">
                    <label for="filter-order" class="text-xs text-zinc-500">Ordenar</label>
                    <NativeSelect id="filter-order" v-model="order" name="order" class="w-40">
                        <NativeSelectOption
                            v-for="option in orderOptions"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </NativeSelectOption>
                    </NativeSelect>
                </div>
            </div>

            <Card>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Data</TableHead>
                                <TableHead>Descrição</TableHead>
                                <TableHead>Origem</TableHead>
                                <TableHead>Tipo</TableHead>
                                <TableHead class="text-right">Valor</TableHead>
                                <TableHead class="text-right">Ações</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableEmpty v-if="!hasEntries" :colspan="6">
                                Nenhuma transação para este período.
                            </TableEmpty>
                            <TableRow v-for="entry in entries" :key="entry.id">
                                <TableCell class="whitespace-nowrap">{{ formatDate(entry.date) }}</TableCell>
                                <TableCell>
                                    <div class="flex flex-col">
                                        <span class="font-medium">{{ entry.label }}</span>
                                        <span
                                            v-if="entry.paid_at"
                                            class="text-xs text-emerald-600 dark:text-emerald-400"
                                        >
                                            Pago em {{ formatDate(entry.paid_at) }}
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell class="text-sm text-zinc-500">{{ entry.source }}</TableCell>
                                <TableCell>
                                    <span
                                        class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="kindStyles[entry.kind]?.class"
                                    >
                                        {{ kindStyles[entry.kind]?.label ?? entry.kind }}
                                    </span>
                                </TableCell>
                                <TableCell class="text-right font-medium whitespace-nowrap" :class="amountClass(entry)">
                                    {{ formatCurrency(signedAmount(entry), entry.currency_symbol) }}
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <Button
                                            v-if="!entry.paid_at"
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            aria-label="Marcar como pago"
                                            @click="payEntry(entry)"
                                        >
                                            <CheckIcon class="size-4" aria-hidden="true" />
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            aria-label="Editar transação"
                                            @click="openEdit(entry)"
                                        >
                                            <PencilIcon class="size-4" aria-hidden="true" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
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
