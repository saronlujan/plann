<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { index as tenantsIndex } from '@/routes/admin/tenants';

defineProps<{
    tenant: {
        id: number;
        name: string;
        plan: string;
        subscribed: boolean;
        on_trial: boolean;
        trial_ends_at: string | null;
        created_at: string | null;
        stripe_id: string | null;
    };
    user: {
        id: number;
        name: string;
        email: string;
        verified: boolean;
        created_at: string | null;
    } | null;
}>();
</script>

<template>
    <Head :title="tenant.name" />

    <AdminLayout>
        <div class="flex flex-col gap-5">
            <Link
                :href="tenantsIndex().url"
                class="w-fit text-sm text-muted-foreground transition hover:text-foreground"
            >
                ← {{ $t('admin.tenants.back') }}
            </Link>

            <PageHeader :title="tenant.name">
                <Badge v-if="tenant.subscribed" variant="outline">
                    {{ $t('admin.status.subscribed') }}
                </Badge>
                <Badge v-else-if="tenant.on_trial" variant="outline">
                    {{ $t('admin.status.trialing') }}
                </Badge>
                <Badge v-else variant="outline">{{ $t('admin.status.lapsed') }}</Badge>
            </PageHeader>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardContent class="flex flex-col gap-3 p-2">
                        <span class="font-medium">{{ $t('admin.show.account') }}</span>
                        <dl class="flex flex-col gap-2 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-muted-foreground">
                                    {{ $t('admin.columns.name') }}
                                </dt>
                                <dd>{{ user?.name ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-muted-foreground">
                                    {{ $t('admin.columns.email') }}
                                </dt>
                                <dd class="text-right break-all">{{ user?.email ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-muted-foreground">
                                    {{ $t('admin.show.verified') }}
                                </dt>
                                <dd>{{ user?.verified ? $t('common.state.yes') : $t('common.state.no') }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-muted-foreground">
                                    {{ $t('admin.columns.created_at') }}
                                </dt>
                                <dd class="tabular-nums">{{ tenant.created_at ?? '—' }}</dd>
                            </div>
                        </dl>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="flex flex-col gap-3 p-2">
                        <span class="font-medium">{{ $t('admin.show.billing') }}</span>
                        <dl class="flex flex-col gap-2 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-muted-foreground">
                                    {{ $t('admin.columns.plan') }}
                                </dt>
                                <dd>{{ tenant.plan }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-muted-foreground">
                                    {{ $t('admin.show.trial_ends_at') }}
                                </dt>
                                <dd class="tabular-nums">{{ tenant.trial_ends_at ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-muted-foreground">
                                    {{ $t('admin.show.stripe_id') }}
                                </dt>
                                <dd class="text-right font-mono text-xs break-all">
                                    {{ tenant.stripe_id ?? '—' }}
                                </dd>
                            </div>
                        </dl>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AdminLayout>
</template>
