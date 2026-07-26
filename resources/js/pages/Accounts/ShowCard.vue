<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { getActiveLanguage } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';
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
import { colorHex } from '@/lib/labelColors';
import { formatMoney } from '@/lib/money';
import DefaultLayout from '@/layouts/DefaultLayout.vue';
import { accounts as accountsIndex } from '@/routes';
import PayInvoiceModal from './components/PayInvoiceModal.vue';

type Entry = {
    id: string;
    date: string;
    description: string;
    amount: string;
    category: string | null;
    color: string | null;
};

const props = defineProps<{
    account: {
        id: number;
        name: string;
        currency_code: string;
        credit_limit: string | null;
        closing_day: number | null;
        due_day: number | null;
    };
    invoice: {
        cycle_start: string;
        cycle_end: string;
        due_date: string;
        total: string;
        outstanding: string;
        available: string | null;
    };
    entries: Entry[];
    payAccounts: { value: string; label: string }[];
}>();

function localeTag(): string {
    return { pt: 'pt-BR', en: 'en-US', es: 'es-AR' }[getActiveLanguage()] ?? 'pt-BR';
}

function formatDay(date: string): string {
    return new Intl.DateTimeFormat(localeTag(), { day: '2-digit', month: '2-digit' }).format(
        new Date(`${date}T00:00:00`),
    );
}

function money(value: string): string {
    return formatMoney(value, props.account.currency_code);
}

const today = computed(() => {
    const now = new Date();

    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
});

const payOpen = ref(false);
</script>

<template>
    <Head :title="account.name" />

    <DefaultLayout>
        <main class="flex flex-col gap-5 p-3 md:p-5">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div class="flex flex-col gap-1">
                    <Link
                        :href="accountsIndex().url"
                        class="text-xs text-muted-foreground hover:underline"
                    >
                        ← {{ $t('accounts.statement.back') }}
                    </Link>
                    <h1 class="text-lg font-semibold md:text-xl">{{ account.name }}</h1>
                    <span class="text-xs text-muted-foreground">
                        {{
                            $t('accounts.invoice.period', {
                                start: formatDay(invoice.cycle_start),
                                end: formatDay(invoice.cycle_end),
                            })
                        }}
                    </span>
                </div>
                <Button :disabled="payAccounts.length === 0" @click="payOpen = true">
                    {{ $t('accounts.invoice.pay.action') }}
                </Button>
            </div>

            <!-- KPIs -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardContent class="flex flex-col gap-0.5 p-4">
                        <span class="text-xs text-muted-foreground">
                            {{ $t('accounts.invoice.total') }}
                        </span>
                        <span class="text-lg font-semibold">{{ money(invoice.total) }}</span>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="flex flex-col gap-0.5 p-4">
                        <span class="text-xs text-muted-foreground">
                            {{ $t('accounts.invoice.due_date') }}
                        </span>
                        <span class="text-lg font-semibold">{{ formatDay(invoice.due_date) }}</span>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="flex flex-col gap-0.5 p-4">
                        <span class="text-xs text-muted-foreground">
                            {{ $t('accounts.invoice.outstanding') }}
                        </span>
                        <span class="text-lg font-semibold text-red-600 dark:text-red-400">
                            {{ money(invoice.outstanding) }}
                        </span>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="flex flex-col gap-0.5 p-4">
                        <span class="text-xs text-muted-foreground">
                            {{ $t('accounts.invoice.available') }}
                        </span>
                        <span class="text-lg font-semibold text-emerald-600 dark:text-emerald-400">
                            {{ invoice.available != null ? money(invoice.available) : '—' }}
                        </span>
                    </CardContent>
                </Card>
            </div>

            <!-- Invoice purchases -->
            <Card v-if="entries.length > 0" class="gap-0 overflow-hidden p-0 md:p-0">
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{{ $t('accounts.invoice.columns.date') }}</TableHead>
                                <TableHead>
                                    {{ $t('accounts.invoice.columns.description') }}
                                </TableHead>
                                <TableHead>{{ $t('accounts.invoice.columns.category') }}</TableHead>
                                <TableHead class="text-right">
                                    {{ $t('accounts.invoice.columns.amount') }}
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="entry in entries" :key="entry.id">
                                <TableCell class="text-sm whitespace-nowrap text-muted-foreground">
                                    {{ formatDay(entry.date) }}
                                </TableCell>
                                <TableCell class="font-medium">{{ entry.description }}</TableCell>
                                <TableCell>
                                    <div v-if="entry.category" class="flex items-center gap-2">
                                        <span
                                            v-if="entry.color"
                                            class="size-2.5 shrink-0 rounded-full"
                                            :style="{ backgroundColor: colorHex(entry.color) }"
                                        />
                                        <span class="text-sm">{{ entry.category }}</span>
                                    </div>
                                    <span v-else class="text-sm text-muted-foreground">—</span>
                                </TableCell>
                                <TableCell class="text-right font-medium whitespace-nowrap">
                                    {{ money(entry.amount) }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
            <div v-else class="p-6 text-center text-sm text-muted-foreground">
                {{ $t('accounts.invoice.empty') }}
            </div>

            <PayInvoiceModal
                v-model:open="payOpen"
                :account-id="account.id"
                :pay-accounts="payAccounts"
                :suggested-amount="invoice.outstanding"
                :today="today"
            />
        </main>
    </DefaultLayout>
</template>
