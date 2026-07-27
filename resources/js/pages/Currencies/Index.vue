<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed } from 'vue';
import { toast } from 'vue-sonner';
import { Alert } from '@/components/ui/alert';
import { Card, CardContent } from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';
import DefaultLayout from '@/layouts/DefaultLayout.vue';
import { accounts } from '@/routes';
import { index as billing } from '@/routes/billing';
import { activations as updateActivations } from '@/routes/currencies';

type CurrencyRow = {
    id: number;
    code: string;
    name: string;
    symbol: string;
    active: boolean;
    custom: boolean;
    has_accounts: boolean;
};

const props = defineProps<{ currencies: CurrencyRow[]; canUseMultiCurrency: boolean }>();

const activeCount = computed(() => props.currencies.filter((row) => row.active).length);

// On a single-currency plan the list is a choice, not a set of toggles: picking
// one swaps out the other instead of failing validation. A workspace carrying
// more than one (grandfathered from Pro) keeps plain toggles so nothing it
// already uses is silently switched off.
const isSingleChoice = computed(() => !props.canUseMultiCurrency && activeCount.value <= 1);

// Activating a currency is only half the setup: without an account in it, the
// currency is invisible everywhere else, so say so until an account exists.
const currenciesWithoutAccounts = computed(() =>
    props.currencies.filter((row) => row.active && !row.has_accounts),
);

// Portuguese and Spanish inflect the whole sentence, not just the noun, so the
// plural needs its own string rather than a suffix.
const missingAccountNotice = computed(() =>
    trans(
        currenciesWithoutAccounts.value.length > 1
            ? 'currencies.missing_account_notice_plural'
            : 'currencies.missing_account_notice',
        { codes: currenciesWithoutAccounts.value.map((row) => row.code).join(', ') },
    ),
);

function toggleCurrency(currency: CurrencyRow, active: boolean): void {
    if (isSingleChoice.value && !active) {
        // Never leave the workspace with no currency at all.
        return;
    }

    const currencyIds =
        isSingleChoice.value && active
            ? [currency.id]
            : props.currencies
                  .filter((row) => (row.id === currency.id ? active : row.active))
                  .map((row) => row.id);

    router.patch(
        updateActivations().url,
        { currency_ids: currencyIds },
        {
            preserveScroll: true,
            onError: (errors) => toast.error(errors.currency_ids ?? trans('common.state.error')),
        },
    );
}
</script>

<template>
    <Head :title="$t('currencies.title')" />

    <DefaultLayout>
        <main class="flex flex-col gap-5 p-3 md:p-5">
            <div class="flex flex-col">
                <h1 class="text-lg font-semibold md:text-xl">{{ $t('currencies.title') }}</h1>
                <span class="text-sm text-muted-foreground">
                    {{
                        isSingleChoice
                            ? $t('currencies.subtitle_single')
                            : $t('currencies.subtitle')
                    }}
                </span>
            </div>

            <!--
                flex is explicit: the alert base is a grid, and tailwind-merge only
                drops that when another display is given.
            -->
            <Alert
                v-if="currenciesWithoutAccounts.length > 0"
                variant="warning"
                class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 py-2.5"
            >
                <span>{{ missingAccountNotice }}</span>
                <Link :href="accounts().url" class="font-medium underline underline-offset-4">
                    {{ $t('currencies.missing_account_cta') }}
                </Link>
            </Alert>

            <Card v-if="!canUseMultiCurrency" class="border-dashed">
                <CardContent class="flex-row flex-wrap items-center justify-between gap-3 p-4">
                    <span class="text-sm text-muted-foreground">
                        {{ $t('currencies.plan_notice') }}
                    </span>
                    <Link
                        :href="billing().url"
                        class="text-sm font-medium underline underline-offset-4"
                    >
                        {{ $t('currencies.plan_cta') }}
                    </Link>
                </CardContent>
            </Card>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <Card v-for="currency in currencies" :key="currency.id" class="p-4 md:p-4">
                    <!--
                        flex-row is explicit: CardContent defaults to flex-col, and
                        tailwind-merge only drops that when the opposite direction is
                        given — otherwise the switch stacks under the currency.
                    -->
                    <CardContent class="flex-row items-center justify-between gap-3">
                        <span class="flex min-w-0 items-center gap-2.5">
                            <span
                                class="flex size-10 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-base font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300"
                            >
                                {{ currency.symbol }}
                            </span>
                            <span class="flex min-w-0 flex-col">
                                <span class="truncate font-medium">{{ currency.code }}</span>
                                <span class="truncate text-xs text-muted-foreground">
                                    {{ currency.name }}
                                </span>
                            </span>
                        </span>

                        <Switch
                            class="ml-auto shrink-0"
                            :model-value="currency.active"
                            :disabled="isSingleChoice && currency.active"
                            :aria-label="$t('currencies.activate', { code: currency.code })"
                            @update:model-value="(value) => toggleCurrency(currency, value)"
                        />
                    </CardContent>
                </Card>
            </div>
        </main>
    </DefaultLayout>
</template>
