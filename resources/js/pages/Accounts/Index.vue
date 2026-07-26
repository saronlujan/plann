<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CreditCardIcon, PlusIcon } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { formatMoney } from '@/lib/money';
import DefaultLayout from '@/layouts/DefaultLayout.vue';
import { show as showAccount } from '@/routes/accounts';
import AccountModal from '../Settings/components/AccountModal.vue';

type Option = { value: string; label: string };
type Account = {
    id: number;
    name: string;
    kind: string;
    currency_code: string;
    balance?: string;
    monthly_income?: string;
    monthly_expense?: string;
    invoice_total?: string;
    invoice_due_date?: string;
    available?: string | null;
    credit_limit?: string | null;
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
                    :aria-label="$t('settings.accounts.add')"
                    :disabled="currencyOptions.length === 0"
                    @click="modalOpen = true"
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
                        <CardContent class="flex flex-col gap-3 p-4">
                            <div class="flex items-center justify-between gap-2">
                                <span class="flex min-w-0 items-center gap-1.5">
                                    <CreditCardIcon
                                        v-if="account.kind === 'credit_card'"
                                        class="size-4 shrink-0 text-muted-foreground"
                                    />
                                    <span class="truncate font-medium">{{ account.name }}</span>
                                </span>
                                <span class="shrink-0 text-xs text-muted-foreground">
                                    {{ account.currency_code }}
                                </span>
                            </div>

                            <template v-if="account.kind === 'credit_card'">
                                <div class="flex flex-col">
                                    <span class="text-xs text-muted-foreground">{{
                                        $t('accounts.invoice.total')
                                    }}</span>
                                    <span class="text-2xl font-semibold">
                                        {{ formatMoney(account.invoice_total ?? '0', account.currency_code) }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between gap-4 text-xs">
                                    <span class="text-muted-foreground">
                                        {{ $t('accounts.invoice.due_date') }}:
                                        {{ account.invoice_due_date ? formatDate(account.invoice_due_date) : '—' }}
                                    </span>
                                    <span v-if="account.available != null" class="text-emerald-600 dark:text-emerald-400">
                                        {{ $t('accounts.invoice.available') }}:
                                        {{ formatMoney(account.available, account.currency_code) }}
                                    </span>
                                </div>
                            </template>

                            <template v-else>
                                <span class="text-2xl font-semibold">
                                    {{ formatMoney(account.balance ?? '0', account.currency_code) }}
                                </span>
                                <div class="flex items-center gap-4 text-xs">
                                    <span class="text-emerald-600 dark:text-emerald-400">
                                        +{{ formatMoney(account.monthly_income ?? '0', account.currency_code) }}
                                    </span>
                                    <span class="text-red-600 dark:text-red-400">
                                        -{{ formatMoney(account.monthly_expense ?? '0', account.currency_code) }}
                                    </span>
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
                :currency-options="currencyOptions"
                :kind-options="kindOptions"
            />
        </main>
    </DefaultLayout>
</template>
