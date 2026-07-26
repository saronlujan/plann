<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { EllipsisVerticalIcon, ThumbsDownIcon, ThumbsUpIcon } from '@lucide/vue';
import { onClickOutside } from '@vueuse/core';
import { trans } from 'laravel-vue-i18n';
import { Plus } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import DefaultLayout from '@/layouts/DefaultLayout.vue';
import { accountKindIcon } from '@/lib/accountKind';
import { playSound } from '@/lib/sound';
import type { SoundValue } from '@/lib/sound';
import transactions from '@/routes/transactions';
import TransactionDetailsDrawer from './components/TransactionDetailsDrawer.vue';
import TransactionModal from './components/TransactionModal.vue';
import {
    amountClass,
    dueStatus,
    movementBadgeClass,
    movementKind,
    movementLabel,
    scheduleLabel,
    signedAmount,
} from './display';
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

const page = usePage<{
    auth: { user: { sound_enabled: boolean; sound_theme: SoundValue } | null };
}>();

function summaryRows(summary: TransactionSummary): { label: string; value: string }[] {
    return [
        {
            label: trans('transactions.summary.income'),
            value: formatCurrency(summary.income, summary.symbol),
        },
        {
            label: trans('transactions.summary.expenses'),
            value: formatCurrency(summary.expenses, summary.symbol),
        },
        {
            label: trans('transactions.summary.total'),
            value: formatCurrency(summary.total, summary.symbol),
        },
        {
            label: trans('transactions.summary.expected_income'),
            value: formatCurrency(summary.expected_income, summary.symbol),
        },
        {
            label: trans('transactions.summary.expected_expense'),
            value: formatCurrency(summary.expected_expense, summary.symbol),
        },
        {
            label: trans('transactions.summary.expected_total'),
            value: formatCurrency(summary.expected_total, summary.symbol),
        },
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

        if (page.props.auth.user?.sound_enabled) {
            playSound(page.props.auth.user.sound_theme);
        }

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
    const parts: string[] = [];

    if (entry.is_transfer && entry.transfer_from && entry.transfer_to) {
        parts.push(scheduleLabel(entry));
        parts.push(`${entry.transfer_from} → ${entry.transfer_to}`);

        return parts.join(' - ');
    }

    const category = props.categoryOptions.find((option) => option.id === entry.category_id);

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
    <Head :title="$t('transactions.title')" />

    <DefaultLayout>
        <main class="flex flex-col gap-5 p-3 md:p-5">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-col">
                    <h1 class="text-lg font-semibold md:text-xl">{{ $t('transactions.title') }}</h1>
                    <span class="text-sm text-muted-foreground">
                        {{ $t('transactions.subtitle') }}
                    </span>
                </div>

                <div ref="createMenuRef" class="relative">
                    <Button
                        class="shrink-0 rounded-full"
                        size="icon-lg"
                        :aria-label="$t('transactions.actions.new')"
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
                                <TableHead>{{ $t('transactions.columns.type') }}</TableHead>
                                <TableHead></TableHead>
                                <TableHead></TableHead>
                                <TableHead></TableHead>
                                <TableHead class="text-right">{{
                                    $t('transactions.columns.amount')
                                }}</TableHead>
                                <TableHead></TableHead>
                                <TableHead></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="entry in entries" :key="entry.id">
                                <TableCell>
                                    <Badge
                                        variant="outline"
                                        :class="movementBadgeClass[movementKind(entry)]"
                                    >
                                        {{ movementLabel(movementKind(entry)) }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <TooltipProvider>
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <span
                                                    class="inline-flex text-muted-foreground"
                                                    :aria-label="entry.source"
                                                >
                                                    <component
                                                        :is="
                                                            accountKindIcon(
                                                                entry.account_kind ?? 'account',
                                                            )
                                                        "
                                                        class="size-[1.05rem]"
                                                    />
                                                </span>
                                            </TooltipTrigger>
                                            <TooltipContent>{{ entry.source }}</TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
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
                                    <Badge v-if="entry.paid_at" class="rounded-full">{{
                                        $t('transactions.status.paid')
                                    }}</Badge>
                                    <Badge
                                        v-else-if="dueStatus(entry) === 'overdue'"
                                        variant="outline"
                                        class="rounded-full border-red-200 bg-red-50 text-red-600 dark:border-red-900 dark:bg-red-950 dark:text-red-400"
                                    >
                                        {{ $t('transactions.status.overdue') }}
                                    </Badge>
                                    <Badge
                                        v-else-if="dueStatus(entry) === 'soon'"
                                        variant="outline"
                                        class="rounded-full border-amber-200 bg-amber-50 text-amber-600 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-400"
                                    >
                                        {{ $t('transactions.status.due_soon') }}
                                    </Badge>
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
                                    <div class="flex items-center justify-end gap-4">
                                        <span class="relative inline-flex">
                                            <button
                                                type="button"
                                                :aria-label="
                                                    entry.paid_at
                                                        ? $t('transactions.actions.mark_unpaid')
                                                        : $t('transactions.actions.mark_paid')
                                                "
                                                :class="
                                                    entry.paid_at
                                                        ? 'text-emerald-600 dark:text-emerald-400'
                                                        : 'text-muted-foreground hover:text-foreground'
                                                "
                                                @click="togglePaid(entry)"
                                            >
                                                <component
                                                    :is="
                                                        entry.paid_at
                                                            ? ThumbsUpIcon
                                                            : ThumbsDownIcon
                                                    "
                                                    class="size-5"
                                                    :class="{
                                                        'thumb-pop':
                                                            poppingId === entry.transaction_id,
                                                    }"
                                                    aria-hidden="true"
                                                />
                                            </button>
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
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <button
                                                    type="button"
                                                    class="text-muted-foreground transition hover:text-foreground"
                                                    :aria-label="
                                                        $t('transactions.actions.more_options')
                                                    "
                                                >
                                                    <EllipsisVerticalIcon class="size-5" />
                                                </button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end" class="w-40">
                                                <DropdownMenuItem @click="openDetails(entry)">
                                                    {{ $t('transactions.actions.view') }}
                                                </DropdownMenuItem>
                                                <DropdownMenuItem @click="openEdit(entry)">
                                                    {{ $t('common.actions.edit') }}
                                                </DropdownMenuItem>
                                                <DropdownMenuItem
                                                    class="text-red-600 focus:text-red-600"
                                                    @click="deleteEntry(entry)"
                                                >
                                                    {{ $t('common.actions.delete') }}
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
            <div v-else class="p-6 text-center text-sm text-muted-foreground">
                {{ $t('common.state.empty') }}
            </div>

            <Card v-if="summaries.length">
                <CardContent>
                    <Tabs :default-value="summaries[0].code">
                        <TabsList v-if="summaries.length > 1">
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
                :title="$t('transactions.delete.title')"
                :description="
                    $t('transactions.delete.description', { label: deleteTarget?.label ?? '' })
                "
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
