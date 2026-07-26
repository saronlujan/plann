<script setup lang="ts">
import { getActiveLanguage } from 'laravel-vue-i18n';
import { Card, CardContent } from '@/components/ui/card';
import { formatMoney } from '@/lib/money';
import DonutChart from './DonutChart.vue';
import TrendChart from './TrendChart.vue';

type SeriesPoint = { label: string; month: string; income: string; expense: string };
type CategorySlice = { name: string; color: string; value: string };
type RecentItem = {
    id: string;
    description: string;
    movement_type: string;
    amount: string;
    date: string;
    paid: boolean;
    account: string;
};
type CurrencyData = {
    code: string;
    symbol: string;
    balance: string;
    monthlyIncome: string;
    monthlyExpenses: string;
    monthlyNet: string;
    series: SeriesPoint[];
    expensesByCategory: CategorySlice[];
    recent: RecentItem[];
};

const props = defineProps<{ data: CurrencyData }>();

function formatDay(date: string): string {
    const localeTag = { pt: 'pt-BR', en: 'en-US', es: 'es-AR' }[getActiveLanguage()] ?? 'pt-BR';

    return new Intl.DateTimeFormat(localeTag, { day: '2-digit', month: 'short' }).format(
        new Date(`${date}T00:00:00`),
    );
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Card>
                <CardContent class="flex flex-col gap-1 p-2">
                    <span class="text-sm text-muted-foreground">{{ $t('dashboard.balance') }}</span>
                    <span class="text-2xl font-semibold">
                        {{ formatMoney(props.data.balance, props.data.code) }}
                    </span>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="flex flex-col gap-1 p-2">
                    <span class="text-sm text-muted-foreground">
                        {{ $t('dashboard.monthly_income') }}
                    </span>
                    <span class="text-2xl font-semibold text-emerald-600 dark:text-emerald-400">
                        {{ formatMoney(props.data.monthlyIncome, props.data.code) }}
                    </span>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="flex flex-col gap-1 p-2">
                    <span class="text-sm text-muted-foreground">
                        {{ $t('dashboard.monthly_expenses') }}
                    </span>
                    <span class="text-2xl font-semibold text-red-600 dark:text-red-400">
                        {{ formatMoney(props.data.monthlyExpenses, props.data.code) }}
                    </span>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <Card class="lg:col-span-2">
                <CardContent class="flex flex-col gap-3 p-2">
                    <span class="font-medium">{{ $t('dashboard.statistics') }}</span>
                    <TrendChart :series="props.data.series" :currency="props.data.code" />
                </CardContent>
            </Card>
            <Card>
                <CardContent class="flex flex-col gap-3 p-2">
                    <span class="font-medium">{{ $t('dashboard.expenses_by_category') }}</span>
                    <DonutChart :segments="props.data.expensesByCategory" :currency="props.data.code" />
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardContent class="flex flex-col gap-1 p-2">
                <span class="mb-2 font-medium">{{ $t('dashboard.recent') }}</span>
                <div
                    v-if="props.data.recent.length === 0"
                    class="py-6 text-center text-sm text-muted-foreground"
                >
                    {{ $t('dashboard.empty_recent') }}
                </div>
                <ul v-else class="divide-y">
                    <li
                        v-for="item in props.data.recent"
                        :key="item.id"
                        class="flex items-center justify-between gap-3 py-2.5"
                    >
                        <div class="flex min-w-0 flex-col">
                            <span class="truncate text-sm font-medium">{{ item.description }}</span>
                            <span class="text-xs text-muted-foreground">
                                {{ formatDay(item.date) }} · {{ item.account }}
                            </span>
                        </div>
                        <span
                            class="shrink-0 text-sm font-medium whitespace-nowrap"
                            :class="
                                item.movement_type === 'expense'
                                    ? 'text-red-600 dark:text-red-400'
                                    : 'text-emerald-600 dark:text-emerald-400'
                            "
                        >
                            {{ item.movement_type === 'expense' ? '-' : '+'
                            }}{{ formatMoney(item.amount, props.data.code) }}
                        </span>
                    </li>
                </ul>
            </CardContent>
        </Card>
    </div>
</template>
