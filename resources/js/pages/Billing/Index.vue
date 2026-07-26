<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
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
import { checkout, portal } from '@/routes/billing';

type Plan = {
    slug: string;
    name: string;
    description: string | null;
    max_users: number;
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

const props = defineProps<{
    plans: Plan[];
    status: Status;
    invoices: Invoice[];
}>();

function money(cents: number): string {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100);
}

const statusText = computed(() => {
    if (props.status.subscribed) {
        return 'Assinatura ativa.';
    }

    if (props.status.on_trial) {
        return `Período de teste: ${props.status.trial_days_left} dia(s) restante(s).`;
    }

    return 'Seu período de teste terminou. Escolha um plano para continuar.';
});

function isCurrent(plan: Plan): boolean {
    return props.status.subscribed && props.status.plan_slug === plan.slug;
}

function actionLabel(plan: Plan): string {
    if (isCurrent(plan)) {
        return 'Plano atual';
    }

    if (!plan.available) {
        return 'Indisponível';
    }

    return props.status.subscribed ? `Trocar para ${plan.name}` : 'Assinar';
}

function subscribe(plan: Plan): void {
    router.post(checkout(plan.slug).url);
}
</script>

<template>
    <Head title="Planos e cobrança" />

    <DefaultLayout>
        <main class="flex flex-col gap-5 p-3 md:p-5">
            <div class="flex flex-col">
                <h1 class="text-lg font-semibold md:text-xl">Planos e cobrança</h1>
                <span class="text-sm text-muted-foreground">
                    Escolha o plano ideal — cobrança anual, cancele quando quiser.
                </span>
            </div>

            <Card>
                <CardContent class="flex flex-wrap items-center justify-between gap-3 p-5">
                    <div class="flex items-center gap-2">
                        <Badge :variant="status.subscribed || status.on_trial ? 'default' : 'destructive'">
                            {{ status.subscribed ? 'Ativo' : status.on_trial ? 'Teste' : 'Expirado' }}
                        </Badge>
                        <span class="text-sm">{{ statusText }}</span>
                    </div>
                    <a
                        v-if="status.subscribed || status.on_grace_period"
                        :href="portal().url"
                        class="text-sm font-medium underline underline-offset-4"
                    >
                        Gerenciar pagamento
                    </a>
                </CardContent>
            </Card>

            <div class="grid gap-4 sm:grid-cols-2">
                <Card v-for="plan in plans" :key="plan.slug">
                    <CardContent class="flex h-full flex-col gap-3 p-5">
                        <div class="flex items-center justify-between">
                            <CardTitle class="text-base">{{ plan.name }}</CardTitle>
                            <Badge v-if="isCurrent(plan)" variant="secondary">Atual</Badge>
                        </div>
                        <CardDescription>{{ plan.description }}</CardDescription>

                        <div class="mt-1">
                            <span class="text-2xl font-semibold">{{ money(plan.monthly_price_cents) }}</span>
                            <span class="text-sm text-muted-foreground">/mês</span>
                            <p class="text-xs text-muted-foreground">
                                cobrado anualmente {{ money(plan.annual_price_cents) }}
                            </p>
                        </div>

                        <p class="text-sm text-muted-foreground">
                            Até {{ plan.max_users }} usuário(s).
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

            <Card>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Fatura</TableHead>
                                <TableHead>Data</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-right">Total</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableEmpty v-if="invoices.length === 0" :colspan="4">
                                Nenhuma fatura ainda.
                            </TableEmpty>
                            <TableRow v-for="invoice in invoices" :key="invoice.id">
                                <TableCell class="font-mono text-xs">{{ invoice.id }}</TableCell>
                                <TableCell>{{ invoice.date }}</TableCell>
                                <TableCell>{{ invoice.status }}</TableCell>
                                <TableCell class="text-right whitespace-nowrap">{{ invoice.total }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </main>
    </DefaultLayout>
</template>
