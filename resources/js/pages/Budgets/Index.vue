<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { PencilIcon, PlusIcon, Trash2Icon } from '@lucide/vue';
import { ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { colorHex } from '@/lib/labelColors';
import { formatMoney } from '@/lib/money';
import DefaultLayout from '@/layouts/DefaultLayout.vue';
import { destroy as destroyBudget } from '@/routes/budgets';
import BudgetModal from './components/BudgetModal.vue';

type CategoryOption = { id: number; name: string; color: string };
type CurrencyOption = { id: number; code: string; name: string };
type Budget = {
    id: number;
    category_id: number;
    category: string;
    color: string;
    currency_id: number;
    currency_code: string;
    amount: string;
    spent: string;
};

defineProps<{
    budgets: Budget[];
    categoryOptions: CategoryOption[];
    currencyOptions: CurrencyOption[];
}>();

function percent(budget: Budget): number {
    const amount = Number.parseFloat(budget.amount);
    const spent = Number.parseFloat(budget.spent);

    return amount > 0 ? Math.min(100, Math.round((spent / amount) * 100)) : 0;
}

function isOver(budget: Budget): boolean {
    return Number.parseFloat(budget.spent) > Number.parseFloat(budget.amount);
}

function barColor(budget: Budget): string {
    return isOver(budget) ? '#ef4444' : colorHex(budget.color);
}

function difference(budget: Budget): string {
    const diff = Number.parseFloat(budget.amount) - Number.parseFloat(budget.spent);

    return formatMoney(Math.abs(diff), budget.currency_code);
}

const modalOpen = ref(false);
const editing = ref<Budget | null>(null);
function openModal(budget: Budget | null): void {
    editing.value = budget;
    modalOpen.value = true;
}

const deleteTarget = ref<Budget | null>(null);
const confirmOpen = ref(false);
function askDelete(budget: Budget): void {
    deleteTarget.value = budget;
    confirmOpen.value = true;
}
function confirmDelete(): void {
    const target = deleteTarget.value;
    if (target === null) {
        return;
    }
    confirmOpen.value = false;
    router.delete(destroyBudget(target.id).url, {
        preserveScroll: true,
        onFinish: () => (deleteTarget.value = null),
    });
}
</script>

<template>
    <Head :title="$t('budgets.title')" />

    <DefaultLayout>
        <main class="flex flex-col gap-5 p-3 md:p-5">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-col">
                    <h1 class="text-lg font-semibold md:text-xl">{{ $t('budgets.title') }}</h1>
                    <span class="text-sm text-muted-foreground">{{ $t('budgets.subtitle') }}</span>
                </div>
                <Button
                    size="icon-lg"
                    class="rounded-full"
                    :aria-label="$t('budgets.add')"
                    :disabled="categoryOptions.length === 0"
                    @click="openModal(null)"
                >
                    <PlusIcon />
                </Button>
            </div>

            <p v-if="categoryOptions.length === 0" class="text-sm text-muted-foreground">
                {{ $t('budgets.no_categories') }}
            </p>

            <div v-if="budgets.length > 0" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Card v-for="budget in budgets" :key="budget.id">
                    <CardContent class="flex flex-col gap-3 p-5">
                        <div class="flex items-center justify-between gap-2">
                            <span class="flex min-w-0 items-center gap-2 font-medium">
                                <span
                                    class="size-3 shrink-0 rounded-full"
                                    :style="{ backgroundColor: colorHex(budget.color) }"
                                />
                                <span class="truncate">{{ budget.category }}</span>
                            </span>
                            <div class="flex shrink-0 items-center gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    :aria-label="$t('common.actions.edit')"
                                    @click="openModal(budget)"
                                >
                                    <PencilIcon class="size-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    :aria-label="$t('common.actions.delete')"
                                    @click="askDelete(budget)"
                                >
                                    <Trash2Icon class="size-4" />
                                </Button>
                            </div>
                        </div>

                        <div class="text-sm">
                            <span class="font-semibold">
                                {{ formatMoney(budget.spent, budget.currency_code) }}
                            </span>
                            <span class="text-muted-foreground">
                                {{ $t('budgets.spent_of') }}
                                {{ formatMoney(budget.amount, budget.currency_code) }}
                            </span>
                        </div>

                        <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-full rounded-full transition-all"
                                :style="{
                                    width: `${percent(budget)}%`,
                                    backgroundColor: barColor(budget),
                                }"
                            />
                        </div>

                        <span
                            class="text-xs"
                            :class="
                                isOver(budget)
                                    ? 'text-red-600 dark:text-red-400'
                                    : 'text-muted-foreground'
                            "
                        >
                            {{
                                isOver(budget)
                                    ? $t('budgets.over', { value: difference(budget) })
                                    : $t('budgets.remaining', { value: difference(budget) })
                            }}
                        </span>
                    </CardContent>
                </Card>
            </div>
            <div
                v-else-if="categoryOptions.length > 0"
                class="p-6 text-center text-sm text-muted-foreground"
            >
                {{ $t('common.state.empty') }}
            </div>

            <BudgetModal
                v-model:open="modalOpen"
                :entry="editing"
                :category-options="categoryOptions"
                :currency-options="currencyOptions"
            />
            <ConfirmDialog
                :open="confirmOpen"
                :description="$t('budgets.delete_confirm', { name: deleteTarget?.category ?? '' })"
                @update:open="(value) => (confirmOpen = value)"
                @confirm="confirmDelete"
            />
        </main>
    </DefaultLayout>
</template>
