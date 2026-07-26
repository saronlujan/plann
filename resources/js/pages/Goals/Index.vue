<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { PencilIcon, PlusIcon, Trash2Icon } from '@lucide/vue';
import { getActiveLanguage } from 'laravel-vue-i18n';
import { reactive, ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { formatMoney } from '@/lib/money';
import DefaultLayout from '@/layouts/DefaultLayout.vue';
import { contribute as contributeGoal, destroy as destroyGoal } from '@/routes/goals';
import GoalModal from './components/GoalModal.vue';

type CurrencyOption = { id: number; code: string; name: string };
type Goal = {
    id: number;
    name: string;
    currency_id: number;
    currency_code: string;
    target_amount: string;
    current_amount: string;
    target_date: string | null;
};

defineProps<{ goals: Goal[]; currencyOptions: CurrencyOption[] }>();

function percent(goal: Goal): number {
    const target = Number.parseFloat(goal.target_amount);
    const current = Number.parseFloat(goal.current_amount);

    return target > 0 ? Math.min(100, Math.round((current / target) * 100)) : 0;
}

function isReached(goal: Goal): boolean {
    return Number.parseFloat(goal.current_amount) >= Number.parseFloat(goal.target_amount);
}

function remaining(goal: Goal): string {
    const diff = Number.parseFloat(goal.target_amount) - Number.parseFloat(goal.current_amount);

    return formatMoney(Math.max(0, diff), goal.currency_code);
}

function formatDate(date: string): string {
    const localeTag = { pt: 'pt-BR', en: 'en-US', es: 'es-AR' }[getActiveLanguage()] ?? 'pt-BR';

    return new Intl.DateTimeFormat(localeTag, {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(`${date}T00:00:00`));
}

const modalOpen = ref(false);
const editing = ref<Goal | null>(null);
function openModal(goal: Goal | null): void {
    editing.value = goal;
    modalOpen.value = true;
}

const contributions = reactive<Record<number, string>>({});
function contribute(goal: Goal): void {
    const amount = contributions[goal.id];
    if (!amount) {
        return;
    }
    router.post(
        contributeGoal(goal.id).url,
        { amount },
        { preserveScroll: true, onSuccess: () => (contributions[goal.id] = '') },
    );
}

const deleteTarget = ref<Goal | null>(null);
const confirmOpen = ref(false);
function askDelete(goal: Goal): void {
    deleteTarget.value = goal;
    confirmOpen.value = true;
}
function confirmDelete(): void {
    const target = deleteTarget.value;
    if (target === null) {
        return;
    }
    confirmOpen.value = false;
    router.delete(destroyGoal(target.id).url, {
        preserveScroll: true,
        onFinish: () => (deleteTarget.value = null),
    });
}
</script>

<template>
    <Head :title="$t('goals.title')" />

    <DefaultLayout>
        <main class="flex flex-col gap-5 p-3 md:p-5">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-col">
                    <h1 class="text-lg font-semibold md:text-xl">{{ $t('goals.title') }}</h1>
                    <span class="text-sm text-muted-foreground">{{ $t('goals.subtitle') }}</span>
                </div>
                <Button
                    size="icon-lg"
                    class="rounded-full"
                    :aria-label="$t('goals.add')"
                    :disabled="currencyOptions.length === 0"
                    @click="openModal(null)"
                >
                    <PlusIcon />
                </Button>
            </div>

            <div v-if="goals.length > 0" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Card v-for="goal in goals" :key="goal.id">
                    <CardContent class="flex flex-col gap-3 p-5">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex min-w-0 flex-col">
                                <span class="truncate font-medium">{{ goal.name }}</span>
                                <span v-if="goal.target_date" class="text-xs text-muted-foreground">
                                    {{
                                        $t('goals.by_date', { date: formatDate(goal.target_date) })
                                    }}
                                </span>
                            </div>
                            <div class="flex shrink-0 items-center gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    :aria-label="$t('common.actions.edit')"
                                    @click="openModal(goal)"
                                >
                                    <PencilIcon class="size-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    :aria-label="$t('common.actions.delete')"
                                    @click="askDelete(goal)"
                                >
                                    <Trash2Icon class="size-4" />
                                </Button>
                            </div>
                        </div>

                        <div class="text-sm">
                            <span class="font-semibold">
                                {{ formatMoney(goal.current_amount, goal.currency_code) }}
                            </span>
                            <span class="text-muted-foreground">
                                {{ $t('goals.of') }}
                                {{ formatMoney(goal.target_amount, goal.currency_code) }}
                            </span>
                        </div>

                        <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-full rounded-full bg-primary transition-all"
                                :style="{ width: `${percent(goal)}%` }"
                            />
                        </div>

                        <span
                            class="text-xs"
                            :class="
                                isReached(goal)
                                    ? 'font-medium text-emerald-600 dark:text-emerald-400'
                                    : 'text-muted-foreground'
                            "
                        >
                            {{
                                isReached(goal)
                                    ? $t('goals.reached')
                                    : $t('goals.remaining', { value: remaining(goal) })
                            }}
                        </span>

                        <form
                            v-if="!isReached(goal)"
                            class="flex items-center gap-2"
                            @submit.prevent="contribute(goal)"
                        >
                            <Input
                                v-model="contributions[goal.id]"
                                type="number"
                                step="0.01"
                                min="0"
                                class="h-9"
                                :placeholder="$t('goals.contribute.placeholder')"
                            />
                            <Button type="submit" size="sm" variant="outline">
                                {{ $t('goals.contribute.submit') }}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
            <div v-else class="p-6 text-center text-sm text-muted-foreground">
                {{ $t('common.state.empty') }}
            </div>

            <GoalModal
                v-model:open="modalOpen"
                :entry="editing"
                :currency-options="currencyOptions"
            />
            <ConfirmDialog
                :open="confirmOpen"
                :description="$t('goals.delete_confirm', { name: deleteTarget?.name ?? '' })"
                @update:open="(value) => (confirmOpen = value)"
                @confirm="confirmDelete"
            />
        </main>
    </DefaultLayout>
</template>
