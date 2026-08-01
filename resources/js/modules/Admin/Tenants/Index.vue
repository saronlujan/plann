<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { SearchIcon } from '@lucide/vue';
import { ref, watch } from 'vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { index as tenantsIndex, show as showTenant } from '@/routes/admin/tenants';

type TenantRow = {
    id: number;
    name: string;
    user: string | null;
    email: string | null;
    plan: string;
    status: 'subscribed' | 'trialing' | 'lapsed';
    created_at: string | null;
};

type Paginated = {
    data: TenantRow[];
    total: number;
    from: number | null;
    to: number | null;
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

const props = defineProps<{ tenants: Paginated; filters: { search: string } }>();

const search = ref(props.filters.search);

// Debounced: typing a name should not be one request per keystroke.
let pending: ReturnType<typeof setTimeout> | undefined;

watch(search, (value) => {
    clearTimeout(pending);
    pending = setTimeout(() => {
        router.get(
            tenantsIndex().url,
            value === '' ? {} : { search: value },
            { preserveScroll: true, preserveState: true, replace: true },
        );
    }, 300);
});

const statusClass: Record<TenantRow['status'], string> = {
    subscribed: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    trialing: 'border-sky-200 bg-sky-50 text-sky-700',
    lapsed: 'border-zinc-200 bg-zinc-50 text-zinc-600',
};
</script>

<template>
    <Head :title="$t('admin.tenants.title')" />

    <AdminLayout>
        <div class="flex flex-col gap-5">
            <PageHeader
                :title="$t('admin.tenants.title')"
                :subtitle="$t('admin.tenants.subtitle', { count: String(tenants.total) })"
            />

            <div class="relative max-w-sm">
                <SearchIcon
                    class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    aria-hidden="true"
                />
                <Input
                    v-model="search"
                    class="pl-9"
                    :placeholder="$t('admin.tenants.search')"
                    :aria-label="$t('admin.tenants.search')"
                />
            </div>

            <Card class="gap-0 overflow-hidden p-0 md:p-0">
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{{ $t('admin.columns.tenant') }}</TableHead>
                                <TableHead>{{ $t('admin.columns.email') }}</TableHead>
                                <TableHead>{{ $t('admin.columns.plan') }}</TableHead>
                                <TableHead>{{ $t('admin.columns.status') }}</TableHead>
                                <TableHead>{{ $t('admin.columns.created_at') }}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="tenant in tenants.data" :key="tenant.id">
                                <TableCell class="font-medium">
                                    <Link :href="showTenant(tenant.id).url" class="hover:underline">
                                        {{ tenant.name }}
                                    </Link>
                                    <span
                                        v-if="tenant.user"
                                        class="block text-xs text-muted-foreground"
                                    >
                                        {{ tenant.user }}
                                    </span>
                                </TableCell>
                                <TableCell class="text-muted-foreground">
                                    {{ tenant.email ?? '—' }}
                                </TableCell>
                                <TableCell>{{ tenant.plan }}</TableCell>
                                <TableCell>
                                    <Badge variant="outline" :class="statusClass[tenant.status]">
                                        {{ $t(`admin.status.${tenant.status}`) }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-muted-foreground tabular-nums">
                                    {{ tenant.created_at ?? '—' }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <div
                        v-if="tenants.data.length === 0"
                        class="p-6 text-center text-sm text-muted-foreground"
                    >
                        {{ $t('admin.tenants.empty') }}
                    </div>
                </CardContent>
            </Card>

            <!-- Prev/next rather than numbered pages: a customer list is scanned
                 or searched, not navigated by page number. -->
            <div v-if="tenants.last_page > 1" class="flex items-center justify-between gap-4">
                <span class="text-sm text-muted-foreground">
                    {{
                        $t('admin.tenants.page', {
                            current: String(tenants.current_page),
                            last: String(tenants.last_page),
                        })
                    }}
                </span>
                <div class="flex gap-2">
                    <Button
                        as-child
                        variant="outline"
                        size="sm"
                        :disabled="tenants.prev_page_url === null"
                    >
                        <Link
                            v-if="tenants.prev_page_url"
                            :href="tenants.prev_page_url"
                            preserve-scroll
                        >
                            {{ $t('admin.tenants.previous') }}
                        </Link>
                        <span v-else>{{ $t('admin.tenants.previous') }}</span>
                    </Button>
                    <Button
                        as-child
                        variant="outline"
                        size="sm"
                        :disabled="tenants.next_page_url === null"
                    >
                        <Link
                            v-if="tenants.next_page_url"
                            :href="tenants.next_page_url"
                            preserve-scroll
                        >
                            {{ $t('admin.tenants.next') }}
                        </Link>
                        <span v-else>{{ $t('admin.tenants.next') }}</span>
                    </Button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
