<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed } from 'vue';
import { ref } from 'vue';
import { Card, CardContent } from '@/components/ui/card';
import DefaultLayout from '@/layouts/DefaultLayout.vue';
import { cn } from '@/lib/utils';
import CurrencyPanel from './components/CurrencyPanel.vue';

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

const props = defineProps<{ ready: boolean; currencies?: CurrencyData[] }>();

const page = usePage<{ auth: { user: { name: string } | null } }>();

const currencies = computed(() => props.currencies ?? []);

const selected = ref(currencies.value[0]?.code ?? '');

const active = computed(
    () => currencies.value.find((currency) => currency.code === selected.value) ?? null,
);

const greeting = computed(() => {
    const hour = new Date().getHours();
    const period = hour < 12 ? 'morning' : hour < 18 ? 'afternoon' : 'evening';

    return trans(`dashboard.greeting.${period}`, { name: page.props.auth.user?.name ?? '' });
});
</script>

<template>
    <Head :title="$t('dashboard.title')" />

    <DefaultLayout>
        <main class="flex flex-col gap-5 p-3 md:p-5">
            <div class="flex flex-col">
                <h1 class="text-xl font-semibold md:text-2xl">{{ greeting }}</h1>
                <span class="text-sm text-muted-foreground">{{ $t('dashboard.subtitle') }}</span>
            </div>

            <Card v-if="!ready || currencies.length === 0">
                <CardContent class="flex flex-col gap-1 p-6 text-center">
                    <span class="font-medium">{{ $t('dashboard.no_currency.title') }}</span>
                    <span class="text-sm text-muted-foreground">
                        {{ $t('dashboard.no_currency.description') }}
                    </span>
                </CardContent>
            </Card>

            <template v-else>
                <div
                    v-if="currencies.length > 1"
                    class="inline-flex w-fit items-center gap-1 rounded-lg bg-muted p-1"
                >
                    <button
                        v-for="currency in currencies"
                        :key="currency.code"
                        type="button"
                        :class="
                            cn(
                                'rounded-md px-3 py-1.5 text-sm font-medium transition',
                                selected === currency.code
                                    ? 'bg-background shadow-sm'
                                    : 'text-muted-foreground hover:text-foreground',
                            )
                        "
                        @click="selected = currency.code"
                    >
                        {{ currency.code }}
                    </button>
                </div>

                <CurrencyPanel v-if="active" :key="active.code" :data="active" />
            </template>
        </main>
    </DefaultLayout>
</template>
