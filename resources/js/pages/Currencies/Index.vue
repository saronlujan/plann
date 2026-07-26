<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent } from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';
import DefaultLayout from '@/layouts/DefaultLayout.vue';
import { update as updateCurrencies } from '@/routes/currencies';

type CurrencyRow = { id: number; code: string; name: string; symbol: string; active: boolean };

const props = defineProps<{ currencies: CurrencyRow[] }>();

function toggleCurrency(currency: CurrencyRow, active: boolean): void {
    const currencyIds = props.currencies
        .filter((row) => (row.id === currency.id ? active : row.active))
        .map((row) => row.id);

    router.patch(updateCurrencies().url, { currency_ids: currencyIds }, { preserveScroll: true });
}
</script>

<template>
    <Head :title="$t('currencies.title')" />

    <DefaultLayout>
        <main class="flex flex-col gap-5 p-3 md:p-5">
            <div class="flex flex-col">
                <h1 class="text-lg font-semibold md:text-xl">
                    {{ $t('currencies.title') }}
                </h1>
                <span class="text-sm text-muted-foreground">
                    {{ $t('currencies.subtitle') }}
                </span>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <Card v-for="currency in currencies" :key="currency.id">
                    <CardContent class="flex items-center justify-between gap-3 p-4">
                        <div class="flex flex-col">
                            <span class="font-medium">{{ currency.code }}</span>
                            <span class="text-xs text-muted-foreground">
                                {{ currency.symbol }} · {{ currency.name }}
                            </span>
                        </div>
                        <Switch
                            :model-value="currency.active"
                            :aria-label="$t('currencies.activate', { code: currency.code })"
                            @update:model-value="(value) => toggleCurrency(currency, value)"
                        />
                    </CardContent>
                </Card>
            </div>
        </main>
    </DefaultLayout>
</template>
