<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { PencilIcon, ThumbsDownIcon, ThumbsUpIcon, Trash2Icon } from '@lucide/vue';
import { onClickOutside } from '@vueuse/core';
import { Plus } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import DefaultLayout from '@/layouts/DefaultLayout.vue';
import transactions from '@/routes/transactions';
import TransactionDetailsDrawer from './components/TransactionDetailsDrawer.vue';
import TransactionModal from './components/TransactionModal.vue';
import { amountClass, dueStatus, movementBadge, scheduleLabel, signedAmount } from './display';
import { formatCurrency, formatDate } from './format';
import type {
    AccountOption,
    CategoryOption,
    CurrencyOption,
    Option,
    TagOption,
    TransactionEntry,
    TransactionSummary,
} from './types';

const props = defineProps<{
    movementTypeOptions: Option[];
    scheduleTypeOptions: Option[];
    frequencyOptions: Option[];
    currencyOptions: CurrencyOption[];
    accountOptions: AccountOption[];
    categoryOptions: CategoryOption[];
    tagOptions: TagOption[];
    entries: TransactionEntry[];
    summaries: TransactionSummary[];
}>();

function summaryRows(summary: TransactionSummary): { label: string; value: string }[] {
    return [
        { label: 'Receita', value: formatCurrency(summary.income, summary.symbol) },
        { label: 'Despesas', value: formatCurrency(summary.expenses, summary.symbol) },
        { label: 'Total', value: formatCurrency(summary.total, summary.symbol) },
        {
            label: 'Receita prevista',
            value: formatCurrency(summary.expected_income, summary.symbol),
        },
        {
            label: 'Despesa prevista',
            value: formatCurrency(summary.expected_expense, summary.symbol),
        },
        { label: 'Total previsto', value: formatCurrency(summary.expected_total, summary.symbol) },
    ];
}

const modalOpen = ref(false);
const editingEntry = ref<TransactionEntry | null>(null);
const initialMovementType = ref('expense');

const createMenuOpen = ref(false);
const createMenuRef = ref<HTMLElement | null>(null);

const movementDot: Record<string, string> = {
    income: 'bg-emerald-500',
    expense: 'bg-red-500',
    transfer: 'bg-sky-500',
};

onClickOutside(createMenuRef, () => (createMenuOpen.value = false));

function startCreate(movementType: string): void {
    initialMovementType.value = movementType;
    editingEntry.value = null;
    createMenuOpen.value = false;
    modalOpen.value = true;
}

function openEdit(entry: TransactionEntry): void {
    editingEntry.value = entry;
    modalOpen.value = true;
}

// Plays the celebratory "like" burst only when marking as paid (not when undoing).
const poppingId = ref<number | null>(null);

function togglePaid(entry: TransactionEntry): void {
    if (!entry.paid_at) {
        poppingId.value = entry.transaction_id;
        window.setTimeout(() => {
            if (poppingId.value === entry.transaction_id) {
                poppingId.value = null;
            }
        }, 650);
    }

    router.patch(transactions.pay(entry.transaction_id).url, {}, { preserveScroll: true });
}

const deleteTarget = ref<TransactionEntry | null>(null);
const confirmOpen = ref(false);

function deleteEntry(entry: TransactionEntry): void {
    deleteTarget.value = entry;
    confirmOpen.value = true;
}

function confirmDelete(): void {
    const target = deleteTarget.value;

    if (target === null) {
        return;
    }

    confirmOpen.value = false;
    router.delete(transactions.destroy(target.transaction_id).url, {
        preserveScroll: true,
        onFinish: () => (deleteTarget.value = null),
    });
}

const hasEntries = computed(() => props.entries.length > 0);

// Subtitle shown under the transaction name: "Categoria - Tipo" (the schedule
// type is omitted for one-off entries, and the category is omitted when absent).
function entrySubtitle(entry: TransactionEntry): string {
    const category = props.categoryOptions.find((option) => option.id === entry.category_id);
    const parts: string[] = [];

    if (category) {
        parts.push(category.name);
    }

    if (entry.schedule_type !== 'unique' || parts.length === 0) {
        parts.push(scheduleLabel(entry));
    }

    return parts.join(' - ');
}

const detailsOpen = ref(false);
const detailsEntry = ref<TransactionEntry | null>(null);

function openDetails(entry: TransactionEntry): void {
    detailsEntry.value = entry;
    detailsOpen.value = true;
}
</script>

<template>
    <Head title="Transações" />

    <DefaultLayout>
        <main class="flex flex-col gap-5 p-3 md:p-5">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-col">
                    <h1 class="text-lg font-semibold md:text-xl">Transactions</h1>
                    <span class="text-sm text-muted-foreground">
                        Manage your financial transactions, including income, expenses, and
                        transfers.
                    </span>
                </div>

                <div ref="createMenuRef" class="relative">
                    <Button
                        class="shrink-0 rounded-full"
                        size="icon-lg"
                        aria-label="Nova transação"
                        :aria-expanded="createMenuOpen"
                        @click="createMenuOpen = !createMenuOpen"
                    >
                        <Plus />
                    </Button>

                    <div
                        v-if="createMenuOpen"
                        class="absolute right-0 z-50 mt-2 w-52 overflow-hidden rounded-md border bg-popover p-1 text-popover-foreground shadow-md"
                    >
                        <button
                            v-for="option in movementTypeOptions"
                            :key="option.value"
                            type="button"
                            class="flex w-full items-center gap-2 rounded-sm px-3 py-2 text-sm transition hover:bg-accent hover:text-accent-foreground"
                            @click="startCreate(option.value)"
                        >
                            <span
                                class="size-2.5 rounded-full"
                                :class="movementDot[option.value]"
                            ></span>
                            {{ option.label }}
                        </button>
                    </div>
                </div>
            </div>

            <Card v-if="hasEntries" class="gap-0 overflow-hidden p-0 md:p-0">
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Tipo</TableHead>
                                <TableHead></TableHead>
                                <TableHead></TableHead>
                                <TableHead>Conta</TableHead>
                                <TableHead class="text-right">Valor</TableHead>
                                <TableHead></TableHead>
                                <TableHead></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="entry in entries" :key="entry.id">
                                <TableCell>
                                    <Badge
                                        variant="outline"
                                        :class="movementBadge[entry.movement_type]?.class"
                                    >
                                        {{
                                            movementBadge[entry.movement_type]?.label ??
                                            entry.movement_type
                                        }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div class="flex flex-col items-start">
                                        <button
                                            type="button"
                                            class="text-left font-medium hover:underline"
                                            @click="openDetails(entry)"
                                        >
                                            {{ entry.description }}
                                        </button>
                                        <span class="text-xs text-muted-foreground">{{
                                            entrySubtitle(entry)
                                        }}</span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge v-if="entry.paid_at" class="rounded-full">Pago</Badge>
                                    <Badge
                                        v-else-if="dueStatus(entry) === 'overdue'"
                                        variant="outline"
                                        class="rounded-full border-red-200 bg-red-50 text-red-600 dark:border-red-900 dark:bg-red-950 dark:text-red-400"
                                    >
                                        Vencida
                                    </Badge>
                                    <Badge
                                        v-else-if="dueStatus(entry) === 'soon'"
                                        variant="outline"
                                        class="rounded-full border-amber-200 bg-amber-50 text-amber-600 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-400"
                                    >
                                        Vence em breve
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-sm text-muted-foreground">
                                    {{ entry.source }}
                                </TableCell>
                                <TableCell
                                    class="text-right font-medium whitespace-nowrap"
                                    :class="amountClass(entry)"
                                >
                                    {{
                                        formatCurrency(signedAmount(entry), entry.currency_symbol, {
                                            signed: true,
                                        })
                                    }}
                                </TableCell>
                                <TableCell class="text-sm whitespace-nowrap text-muted-foreground">
                                    {{ formatDate(entry.date) }}
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <span class="relative inline-flex">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="icon"
                                                :aria-label="
                                                    entry.paid_at
                                                        ? 'Marcar como não pago'
                                                        : 'Marcar como pago'
                                                "
                                                :class="
                                                    entry.paid_at
                                                        ? 'text-emerald-600 dark:text-emerald-400'
                                                        : 'text-muted-foreground'
                                                "
                                                @click="togglePaid(entry)"
                                            >
                                                <component
                                                    :is="entry.paid_at ? ThumbsUpIcon : ThumbsDownIcon"
                                                    class="size-4"
                                                    :class="{
                                                        'thumb-pop':
                                                            poppingId === entry.transaction_id,
                                                    }"
                                                    aria-hidden="true"
                                                />
                                            </Button>
                                            <span
                                                v-if="poppingId === entry.transaction_id"
                                                class="pointer-events-none absolute inset-0"
                                                aria-hidden="true"
                                            >
                                                <i
                                                    v-for="n in 6"
                                                    :key="n"
                                                    class="thumb-particle"
                                                    :style="{ '--i': n }"
                                                />
                                            </span>
                                        </span>
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
                    </Table>
                </CardContent>
            </Card>
            <div v-else class="p-6 text-center text-sm text-muted-foreground">empty</div>

            <Card v-if="summaries.length">
                <CardContent>
                    <Tabs :default-value="summaries[0].code">
                        <TabsList>
                            <TabsTrigger
                                v-for="summary in summaries"
                                :key="summary.code"
                                :value="summary.code"
                            >
                                {{ summary.code }}
                            </TabsTrigger>
                        </TabsList>

                        <TabsContent
                            v-for="summary in summaries"
                            :key="summary.code"
                            :value="summary.code"
                            class="mt-2"
                        >
                            <div class="divide-y">
                                <div
                                    v-for="row in summaryRows(summary)"
                                    :key="row.label"
                                    class="flex items-center justify-between py-2 text-sm"
                                >
                                    <span class="text-muted-foreground">{{ row.label }}</span>
                                    <span class="font-medium whitespace-nowrap">{{
                                        row.value
                                    }}</span>
                                </div>
                            </div>
                        </TabsContent>
                    </Tabs>
                </CardContent>
            </Card>

            <TransactionModal
                v-model:open="modalOpen"
                :entry="editingEntry"
                :initial-movement-type="initialMovementType"
                :currency-options="currencyOptions"
                :account-options="accountOptions"
                :movement-type-options="movementTypeOptions"
                :schedule-type-options="scheduleTypeOptions"
                :frequency-options="frequencyOptions"
                :category-options="categoryOptions"
                :tag-options="tagOptions"
            />

            <TransactionDetailsDrawer
                v-model:open="detailsOpen"
                :entry="detailsEntry"
                :category-options="categoryOptions"
                :tag-options="tagOptions"
            />

            <ConfirmDialog
                :open="confirmOpen"
                title="Excluir transação"
                :description="`Excluir “${deleteTarget?.label}”? Esta ação não pode ser desfeita.`"
                @update:open="(value) => (confirmOpen = value)"
                @confirm="confirmDelete"
            />
        </main>
    </DefaultLayout>
</template>

<style scoped>
/* YouTube-style "like" reaction: the thumb springs up and a burst of green
   particles radiates out when a transaction is marked as paid. */
.thumb-pop {
    animation: thumb-pop 0.45s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes thumb-pop {
    0% {
        transform: scale(1) rotate(0deg);
    }
    35% {
        transform: scale(1.55) rotate(-14deg);
    }
    65% {
        transform: scale(0.85) rotate(6deg);
    }
    100% {
        transform: scale(1) rotate(0deg);
    }
}

.thumb-particle {
    position: absolute;
    top: 50%;
    left: 50%;
    height: 5px;
    width: 5px;
    border-radius: 9999px;
    background-color: #10b981;
    opacity: 0;
    transform: translate(-50%, -50%);
    animation: thumb-particle 0.6s ease-out forwards;
}

@keyframes thumb-particle {
    0% {
        opacity: 1;
        transform: translate(-50%, -50%) rotate(calc(var(--i) * 60deg)) translateY(0) scale(1);
    }
    100% {
        opacity: 0;
        transform: translate(-50%, -50%) rotate(calc(var(--i) * 60deg)) translateY(-18px) scale(0.3);
    }
}
</style>
