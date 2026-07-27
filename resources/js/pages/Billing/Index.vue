<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { CheckIcon } from '@lucide/vue';
import { trans } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import DefaultLayout from '@/layouts/DefaultLayout.vue';
import { checkout, portal, refresh } from '@/routes/billing';

type Plan = {
    slug: string;
    name: string;
    description: string | null;
    features: string[];
    monthly_price_cents: number;
    annual_price_cents: number;
    available: boolean;
};

type Status = {
    plan_slug: string | null;
    subscribed: boolean;
    on_trial: boolean;
    on_grace_period: boolean;
    trial_ends_at: string | null;
    trial_days_left: number;
    current_price_id: string | null;
};

type Invoice = { id: string; date: string; total: string; status: string };

// Mirrors the transactions list: status is a rounded badge, coloured by meaning
// rather than printed as the raw Stripe string.
const invoiceBadgeClass: Record<string, string> = {
    paid: 'border-emerald-200 bg-emerald-50 text-emerald-600 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-400',
    open: 'border-amber-200 bg-amber-50 text-amber-600 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-400',
    draft: 'border-zinc-200 bg-zinc-50 text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400',
    uncollectible:
        'border-red-200 bg-red-50 text-red-600 dark:border-red-900 dark:bg-red-950 dark:text-red-400',
    void: 'border-zinc-200 bg-zinc-50 text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-500',
};

function invoiceStatusLabel(status: string): string {
    const key = `billing.invoices.statuses.${status}`;
    const label = trans(key);

    // Stripe may add statuses we have not translated; show the raw value rather
    // than the untranslated key.
    return label === key ? status : label;
}

const refreshing = ref(false);

function refreshSubscription(): void {
    router.post(
        refresh().url,
        {},
        {
            preserveScroll: true,
            onStart: () => (refreshing.value = true),
            onFinish: () => (refreshing.value = false),
        },
    );
}

const props = defineProps<{
    plans: Plan[];
    status: Status;
    invoices: Invoice[];
}>();

function money(cents: number): string {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(
        cents / 100,
    );
}

const statusText = computed(() => {
    if (props.status.subscribed) {
        return trans('billing.status.subscribed');
    }

    if (props.status.on_trial) {
        return trans('billing.status.trial_days', {
            count: String(props.status.trial_days_left),
        });
    }

    return trans('billing.status.trial_ended');
});

function isCurrent(plan: Plan): boolean {
    return props.status.subscribed && props.status.plan_slug === plan.slug;
}

function actionLabel(plan: Plan): string {
    if (isCurrent(plan)) {
        return trans('billing.actions.current');
    }

    if (!plan.available) {
        return trans('billing.actions.unavailable');
    }

    return props.status.subscribed
        ? trans('billing.actions.switch_to', { name: plan.name })
        : trans('billing.actions.subscribe');
}

function subscribe(plan: Plan): void {
    router.post(checkout(plan.slug).url);
}
</script>

<template>
    <Head :title="$t('billing.title')" />

    <DefaultLayout>
        <main class="flex flex-col gap-5 p-3 md:p-5">
            <div class="flex flex-col">
                <h1 class="text-lg font-semibold md:text-xl">{{ $t('billing.title') }}</h1>
                <span class="text-sm text-muted-foreground">
                    {{ $t('billing.subtitle') }}
                </span>
            </div>

            <Card>
                <CardContent class="flex-row flex-wrap items-center justify-between gap-3 p-5">
                    <div class="flex items-center gap-2">
                        <Badge
                            :variant="
                                status.subscribed || status.on_trial ? 'default' : 'destructive'
                            "
                        >
                            {{
                                status.subscribed
                                    ? $t('billing.status.active')
                                    : status.on_trial
                                      ? $t('billing.status.trial')
                                      : $t('billing.status.expired')
                            }}
                        </Badge>
                        <span class="text-sm">{{ statusText }}</span>
                    </div>
                    <a
                        v-if="status.subscribed || status.on_grace_period"
                        :href="portal().url"
                        class="text-sm font-medium underline underline-offset-4"
                    >
                        {{ $t('billing.manage_payment') }}
                    </a>
                    <!--
                        Escape hatch for someone who paid but is still locked out:
                        pulls the subscription from Stripe on demand.
                    -->
                    <Button
                        v-else
                        type="button"
                        variant="outline"
                        size="sm"
                        :disabled="refreshing"
                        @click="refreshSubscription"
                    >
                        {{ $t('billing.refresh.action') }}
                    </Button>
                </CardContent>
            </Card>

            <div class="grid gap-4 sm:grid-cols-2">
                <Card v-for="plan in plans" :key="plan.slug">
                    <CardContent class="flex h-full flex-col gap-3 p-5">
                        <div class="flex items-center justify-between">
                            <CardTitle class="text-base">{{ plan.name }}</CardTitle>
                            <Badge v-if="isCurrent(plan)" variant="secondary">{{
                                $t('billing.plan.current_badge')
                            }}</Badge>
                        </div>
                        <CardDescription>{{ plan.description }}</CardDescription>

                        <div class="mt-1">
                            <span class="text-2xl font-semibold">{{
                                money(plan.monthly_price_cents)
                            }}</span>
                            <span class="text-sm text-muted-foreground">{{
                                $t('billing.plan.per_month')
                            }}</span>
                            <p class="text-xs text-muted-foreground">
                                {{
                                    $t('billing.plan.billed_annually', {
                                        value: money(plan.annual_price_cents),
                                    })
                                }}
                            </p>
                        </div>

                        <ul
                            v-if="plan.features.length > 0"
                            class="space-y-1 text-sm text-muted-foreground"
                        >
                            <li
                                v-for="feature in plan.features"
                                :key="feature"
                                class="flex items-center gap-2"
                            >
                                <CheckIcon class="size-4 shrink-0" aria-hidden="true" />
                                <span>{{ feature }}</span>
                            </li>
                        </ul>
                        <p v-else class="text-sm text-muted-foreground">
                            {{ $t('billing.plan.no_features') }}
                        </p>

                        <Button
                            class="mt-auto"
                            :disabled="isCurrent(plan) || !plan.available"
                            :variant="isCurrent(plan) ? 'outline' : 'default'"
                            @click="subscribe(plan)"
                        >
                            {{ actionLabel(plan) }}
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <h2 class="font-medium">{{ $t('billing.invoices.title') }}</h2>

            <Card class="gap-0 overflow-hidden p-0 md:p-0">
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{{ $t('billing.invoices.date') }}</TableHead>
                                <TableHead>{{ $t('billing.invoices.invoice') }}</TableHead>
                                <TableHead>{{ $t('billing.invoices.status') }}</TableHead>
                                <TableHead class="text-right">{{
                                    $t('billing.invoices.total')
                                }}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableEmpty v-if="invoices.length === 0" :colspan="4">
                                {{ $t('billing.invoices.empty') }}
                            </TableEmpty>
                            <TableRow v-for="invoice in invoices" :key="invoice.id">
                                <TableCell class="text-sm whitespace-nowrap text-muted-foreground">
                                    {{ invoice.date }}
                                </TableCell>
                                <TableCell class="font-mono text-xs">{{ invoice.id }}</TableCell>
                                <TableCell>
                                    <Badge
                                        variant="outline"
                                        class="rounded-full"
                                        :class="invoiceBadgeClass[invoice.status]"
                                    >
                                        {{ invoiceStatusLabel(invoice.status) }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right font-medium whitespace-nowrap">
                                    {{ invoice.total }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </main>
    </DefaultLayout>
</template>
