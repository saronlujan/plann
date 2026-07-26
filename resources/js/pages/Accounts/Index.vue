<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { PlusIcon } from '@lucide/vue';
import { trans } from 'laravel-vue-i18n';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import Sparkline from '@/components/Sparkline.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { accountKindIcon } from '@/lib/accountKind';
import { formatMoney } from '@/lib/money';
import DefaultLayout from '@/layouts/DefaultLayout.vue';
import { destroy as destroyAccount, show as showAccount } from '@/routes/accounts';
import AccountModal from './components/AccountModal.vue';

type Option = { value: string; label: string };
type Account = {
    id: number;
    name: string;
    kind: string;
    currency_id: number;
    currency_code: string;
    balance: string;
    credit_limit: string | null;
    closing_day: number | null;
    due_day: number | null;
    current_balance?: string;
    monthly_income?: string;
    monthly_expense?: string;
    invoice_total?: string;
    invoice_due_date?: string;
    available?: string | null;
    spark: number[];
};
defineProps<{
    accounts: Account[];
    currencyOptions: Option[];
    kindOptions: Option[];
}>();

function formatDate(value: string): string {
    return new Date(`${value}T00:00:00`).toLocaleDateString(undefined, {
        day: '2-digit',
        month: '2-digit',
    });
}

const modalOpen = ref(false);
const editing = ref<Account | null>(null);
function openCreate(): void {
    editing.value = null;
    modalOpen.value = true;
}
function openEdit(account: Account): void {
    editing.value = account;
    modalOpen.value = true;
}

const deleteTarget = ref<Account | null>(null);
const confirmOpen = ref(false);
function askDelete(account: Account): void {
    deleteTarget.value = account;
    confirmOpen.value = true;
}
function confirmDelete(): void {
    const target = deleteTarget.value;
    if (target === null) {
        return;
    }
    confirmOpen.value = false;
    router.delete(destroyAccount(target.id).url, {
        preserveScroll: true,
        onError: (errors) => {
            toast.error(errors.account ?? trans('common.state.error'));
        },
        onFinish: () => (deleteTarget.value = null),
    });
}
</script>

<template>
    <Head :title="$t('accounts.title')" />

    <DefaultLayout>
        <main class="flex flex-col gap-5 p-3 md:p-5">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-col">
                    <h1 class="text-lg font-semibold md:text-xl">{{ $t('accounts.title') }}</h1>
                    <span class="text-sm text-muted-foreground">{{ $t('accounts.subtitle') }}</span>
                </div>
                <Button
                    size="icon-lg"
                    class="rounded-full"
                    :aria-label="$t('accounts.add')"
                    :disabled="currencyOptions.length === 0"
                    @click="openCreate"
                >
                    <PlusIcon />
                </Button>
            </div>

            <div v-if="accounts.length > 0" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="account in accounts"
                    :key="account.id"
                    :href="showAccount(account.id).url"
                    class="block rounded-xl transition hover:opacity-90"
                >
                    <Card>
                        <CardContent class="relative flex flex-col gap-3 p-2">
                            <Sparkline
                                v-if="account.spark && account.spark.length > 1"
                                :points="account.spark"
                                class="pointer-events-none absolute right-2 bottom-2 opacity-80"
                            />
                            <div class="flex items-center justify-between gap-2">
                                <span class="flex min-w-0 items-center gap-2.5">
                                    <span
                                        class="flex size-10 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300"
                                    >
                                        <component
                                            :is="accountKindIcon(account.kind)"
                                            class="size-5"
                                        />
                                    </span>
                                    <span class="flex min-w-0 flex-col">
                                        <span class="truncate font-medium">{{ account.name }}</span>
                                        <span class="text-xs text-muted-foreground">
                                            {{ account.currency_code }}
                                        </span>
                                    </span>
                                </span>
                                <RowActionsMenu
                                    @edit="openEdit(account)"
                                    @delete="askDelete(account)"
                                />
                            </div>

                            <template v-if="account.kind === 'credit_card'">
                                <div class="flex flex-col">
                                    <span class="text-xs text-muted-foreground">{{
                                        $t('accounts.invoice.total')
                                    }}</span>
                                    <span class="text-2xl font-semibold">
                                        {{
                                            formatMoney(
                                                account.invoice_total ?? '0',
                                                account.currency_code,
                                            )
                                        }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between gap-4 text-xs">
                                    <span class="text-muted-foreground">
                                        {{ $t('accounts.invoice.due_date') }}:
                                        {{
                                            account.invoice_due_date
                                                ? formatDate(account.invoice_due_date)
                                                : '—'
                                        }}
                                    </span>
                                    <span
                                        v-if="account.available != null"
                                        class="text-emerald-600 dark:text-emerald-400"
                                    >
                                        {{ $t('accounts.invoice.available') }}:
                                        {{ formatMoney(account.available, account.currency_code) }}
                                    </span>
                                </div>
                            </template>

                            <template v-else>
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-2xl font-semibold">
                                        {{
                                            formatMoney(
                                                account.current_balance ?? '0',
                                                account.currency_code,
                                            )
                                        }}
                                    </span>
                                    <div class="flex items-center gap-4 text-xs font-semibold">
                                        <span class="text-emerald-600 dark:text-emerald-400">
                                            +{{
                                                formatMoney(
                                                    account.monthly_income ?? '0',
                                                    account.currency_code,
                                                )
                                            }}
                                        </span>
                                        <span class="text-red-600 dark:text-red-400">
                                            -{{
                                                formatMoney(
                                                    account.monthly_expense ?? '0',
                                                    account.currency_code,
                                                )
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </template>
                        </CardContent>
                    </Card>
                </Link>
            </div>
            <div v-else class="p-6 text-center text-sm text-muted-foreground">
                {{ $t('accounts.empty') }}
            </div>

            <AccountModal
                v-model:open="modalOpen"
                :entry="editing"
                :currency-options="currencyOptions"
                :kind-options="kindOptions"
            />
            <ConfirmDialog
                :open="confirmOpen"
                :description="$t('accounts.delete_confirm', { name: deleteTarget?.name ?? '' })"
                @update:open="(value) => (confirmOpen = value)"
                @confirm="confirmDelete"
            />
        </main>
    </DefaultLayout>
</template>
