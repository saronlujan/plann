<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Card, CardContent } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { show as showTenant } from '@/routes/admin/tenants';

type RecentTenant = {
    id: number;
    name: string;
    email: string | null;
    plan: string;
    created_at: string | null;
};

const props = defineProps<{
    stats: {
        tenants: number;
        subscribers: number;
        trialing: number;
        monthly_revenue_cents: number;
    };
    recent: RecentTenant[];
}>();

/** Cents on the wire so no rounding happens before it reaches the screen. */
const revenue = new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: 'BRL',
}).format(props.stats.monthly_revenue_cents / 100);

const cards = [
    { key: 'tenants', label: 'admin.stats.tenants', value: String(props.stats.tenants) },
    { key: 'subscribers', label: 'admin.stats.subscribers', value: String(props.stats.subscribers) },
    { key: 'trialing', label: 'admin.stats.trialing', value: String(props.stats.trialing) },
    { key: 'revenue', label: 'admin.stats.revenue', value: revenue, hint: 'admin.stats.revenue_hint' },
];
</script>

<template>
    <Head :title="$t('admin.dashboard.title')" />

    <AdminLayout>
        <div class="flex flex-col gap-5">
            <PageHeader
                :title="$t('admin.dashboard.title')"
                :subtitle="$t('admin.dashboard.subtitle')"
            />

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card v-for="card in cards" :key="card.key">
                    <CardContent class="flex flex-col gap-1 p-2">
                        <span class="text-sm text-muted-foreground">{{ $t(card.label) }}</span>
                        <span class="text-2xl font-semibold tabular-nums">{{ card.value }}</span>
                        <span v-if="card.hint" class="text-xs text-muted-foreground">
                            {{ $t(card.hint) }}
                        </span>
                    </CardContent>
                </Card>
            </div>

            <Card class="gap-0 overflow-hidden p-0 md:p-0">
                <CardContent class="p-0">
                    <div class="px-4 py-3">
                        <span class="font-medium">{{ $t('admin.dashboard.recent') }}</span>
                    </div>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{{ $t('admin.columns.tenant') }}</TableHead>
                                <TableHead>{{ $t('admin.columns.email') }}</TableHead>
                                <TableHead>{{ $t('admin.columns.plan') }}</TableHead>
                                <TableHead>{{ $t('admin.columns.created_at') }}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="tenant in recent" :key="tenant.id">
                                <TableCell class="font-medium">
                                    <Link :href="showTenant(tenant.id).url" class="hover:underline">
                                        {{ tenant.name }}
                                    </Link>
                                </TableCell>
                                <TableCell class="text-muted-foreground">
                                    {{ tenant.email ?? '—' }}
                                </TableCell>
                                <TableCell>{{ tenant.plan }}</TableCell>
                                <TableCell class="text-muted-foreground tabular-nums">
                                    {{ tenant.created_at ?? '—' }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
