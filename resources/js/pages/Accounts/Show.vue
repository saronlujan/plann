<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronLeftIcon, ChevronRightIcon } from '@lucide/vue';
import { getActiveLanguage } from 'laravel-vue-i18n';
import { computed } from 'vue';
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
import DefaultLayout from '@/layouts/DefaultLayout.vue';
import { colorHex } from '@/lib/labelColors';
import { formatMoney } from '@/lib/money';
import { accounts as accountsIndex } from '@/routes';
import { show as showAccount } from '@/routes/accounts';

type Entry = {
    id: string;
    date: string;
    description: string;
    movement_type: string;
    amount: string;
    paid: boolean;
    balance: string;
    category: string | null;
    color: string | null;
};

const props = defineProps<{
    account: { id: number; name: string; currency_code: string };
    period: string;
    opening: string;
    closing: string;
    income: string;
    expense: string;
    entries: Entry[];
}>();

function localeTag(): string {
    return { pt: 'pt-BR', en: 'en-US', es: 'es-AR' }[getActiveLanguage()] ?? 'pt-BR';
}

function formatDay(date: string): string {
    return new Intl.DateTimeFormat(localeTag(), { day: '2-digit', month: '2-digit' }).format(
        new Date(`${date}T00:00:00`),
    );
}

const periodLabel = computed(() => {
    const [year, month] = props.period.split('-').map(Number);

    return new Intl.DateTimeFormat(localeTag(), { month: 'long', year: 'numeric' }).format(
        new Date(year, month - 1, 1),
    );
});

function shiftPeriod(delta: number): void {
    const [year, month] = props.period.split('-').map(Number);
    const date = new Date(year, month - 1 + delta, 1);
    const next = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;

    router.get(showAccount(props.account.id).url, { period: next }, { preserveScroll: true });
}

function money(value: string): string {
    return formatMoney(value, props.account.currency_code);
}
</script>

<template>
    <Head :title="account.name" />

    <DefaultLayout>
        <main class="flex flex-col gap-5 p-3 md:p-5">
            <div class="flex flex-col gap-1">
                <Link
                    :href="accountsIndex().url"
                    class="text-xs text-muted-foreground hover:underline"
                >
                    ← {{ $t('accounts.statement.back') }}
                </Link>
                <h1 class="text-lg font-semibold md:text-xl">{{ account.name }}</h1>
            </div>

            <!-- KPIs -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardContent class="flex flex-col gap-0.5 p-4">
                        <span class="text-xs text-muted-foreground">
                            {{ $t('accounts.statement.opening') }}
                        </span>
                        <span class="text-lg font-semibold">{{ money(opening) }}</span>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="flex flex-col gap-0.5 p-4">
                        <span class="text-xs text-muted-foreground">
                            {{ $t('accounts.statement.income') }}
                        </span>
                        <span class="text-lg font-semibold text-emerald-600 dark:text-emerald-400">
                            {{ money(income) }}
                        </span>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="flex flex-col gap-0.5 p-4">
                        <span class="text-xs text-muted-foreground">
                            {{ $t('accounts.statement.expense') }}
                        </span>
                        <span class="text-lg font-semibold text-red-600 dark:text-red-400">
                            {{ money(expense) }}
                        </span>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="flex flex-col gap-0.5 p-4">
                        <span class="text-xs text-muted-foreground">
                            {{ $t('accounts.statement.closing') }}
                        </span>
                        <span class="text-lg font-semibold">{{ money(closing) }}</span>
                    </CardContent>
                </Card>
            </div>

            <!-- Period nav -->
            <div class="flex items-center justify-between gap-4">
                <span class="text-sm font-medium capitalize">{{ periodLabel }}</span>
                <div class="flex items-center gap-1">
                    <Button
                        variant="outline"
                        size="icon"
                        aria-label="prev"
                        @click="shiftPeriod(-1)"
                    >
                        <ChevronLeftIcon class="size-4" />
                    </Button>
                    <Button variant="outline" size="icon" aria-label="next" @click="shiftPeriod(1)">
                        <ChevronRightIcon class="size-4" />
                    </Button>
                </div>
            </div>

            <!-- Statement -->
            <Card v-if="entries.length > 0" class="gap-0 overflow-hidden p-0 md:p-0">
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{{ $t('accounts.statement.columns.date') }}</TableHead>
                                <TableHead>
                                    {{ $t('accounts.statement.columns.description') }}
                                </TableHead>
                                <TableHead>{{ $t('accounts.statement.columns.type') }}</TableHead>
                                <TableHead>{{ $t('accounts.statement.columns.status') }}</TableHead>
                                <TableHead class="text-right">
                                    {{ $t('accounts.statement.columns.amount') }}
                                </TableHead>
                                <TableHead class="text-right">
                                    {{ $t('accounts.statement.columns.balance') }}
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="entry in entries" :key="entry.id">
                                <TableCell class="text-sm whitespace-nowrap text-muted-foreground">
                                    {{ formatDay(entry.date) }}
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <span
                                            v-if="entry.color"
                                            class="size-2.5 shrink-0 rounded-full"
                                            :style="{ backgroundColor: colorHex(entry.color) }"
                                        />
                                        <span class="font-medium">{{ entry.description }}</span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge
                                        variant="outline"
                                        class="rounded-full"
                                        :class="
                                            entry.movement_type === 'income'
                                                ? 'border-transparent bg-zinc-100 text-emerald-600 dark:bg-zinc-800 dark:text-emerald-400'
                                                : 'border-transparent bg-zinc-100 text-red-600 dark:bg-zinc-800 dark:text-red-400'
                                        "
                                    >
                                        {{
                                            entry.movement_type === 'income'
                                                ? $t('accounts.movement.income')
                                                : $t('accounts.movement.expense')
                                        }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <Badge v-if="entry.paid" class="rounded-full">
                                        {{ $t('accounts.statement.status_paid') }}
                                    </Badge>
                                    <span v-else class="text-xs text-muted-foreground">
                                        {{ $t('accounts.statement.status_pending') }}
                                    </span>
                                </TableCell>
                                <TableCell
                                    class="text-right font-medium whitespace-nowrap"
                                    :class="
                                        entry.movement_type === 'income'
                                            ? 'text-emerald-600 dark:text-emerald-400'
                                            : 'text-red-600 dark:text-red-400'
                                    "
                                >
                                    {{ entry.movement_type === 'income' ? '+' : '-'
                                    }}{{ money(entry.amount) }}
                                </TableCell>
                                <TableCell class="text-right font-medium whitespace-nowrap">
                                    {{ money(entry.balance) }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
            <div v-else class="p-6 text-center text-sm text-muted-foreground">
                {{ $t('accounts.statement.empty') }}
            </div>
        </main>
    </DefaultLayout>
</template>
