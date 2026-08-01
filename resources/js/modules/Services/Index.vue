<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { DatabaseIcon, PlusIcon } from '@lucide/vue';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
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
import { destroy as destroyService } from '@/routes/services';
import ServiceModal from './components/ServiceModal.vue';
import type { CurrencyOption, Service } from './types';

const props = defineProps<{ services: Service[]; currencyOptions: CurrencyOption[] }>();

const currencyCodes = computed(
    () => new Map(props.currencyOptions.map((option) => [option.value, option.code])),
);

/** Services quoted per job carry no price, and say so rather than showing zero. */
function priceLabel(service: Service): string | null {
    if (service.default_price === null || service.currency_id === null) {
        return null;
    }

    const code = currencyCodes.value.get(service.currency_id.toString());

    return code ? formatMoney(service.default_price, code) : service.default_price;
}

const modalOpen = ref(false);
const editing = ref<Service | null>(null);
function openModal(service: Service | null): void {
    editing.value = service;
    modalOpen.value = true;
}

const deleteTarget = ref<Service | null>(null);
const confirmOpen = ref(false);
function askDelete(service: Service): void {
    deleteTarget.value = service;
    confirmOpen.value = true;
}
function confirmDelete(): void {
    const target = deleteTarget.value;

    if (target === null) {
        return;
    }

    confirmOpen.value = false;
    router.delete(destroyService(target.id).url, {
        preserveScroll: true,
        onFinish: () => (deleteTarget.value = null),
    });
}
</script>

<template>
    <Head :title="$t('services.title')" />

    <DefaultLayout>
        <main class="flex flex-col gap-5 p-3 md:p-5">
            <PageHeader :title="$t('services.title')" :subtitle="$t('services.subtitle')">
                <Button
                    size="icon-lg"
                    class="rounded-full"
                    :aria-label="$t('services.add')"
                    @click="openModal(null)"
                >
                    <PlusIcon />
                </Button>
            </PageHeader>

            <Card v-if="services.length > 0" class="gap-0 overflow-hidden p-0 md:p-0">
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{{ $t('services.columns.name') }}</TableHead>
                                <TableHead>{{ $t('services.columns.default_price') }}</TableHead>
                                <TableHead class="text-right"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="service in services" :key="service.id">
                                <TableCell class="font-medium">
                                    <span class="flex items-center gap-2">
                                        <span
                                            class="size-3 shrink-0 rounded-full"
                                            :style="{ backgroundColor: colorHex(service.color) }"
                                        />
                                        {{ service.name }}
                                    </span>
                                </TableCell>
                                <TableCell
                                    :class="
                                        priceLabel(service) === null
                                            ? 'text-sm text-muted-foreground'
                                            : 'tabular-nums'
                                    "
                                >
                                    {{ priceLabel(service) ?? $t('services.no_price') }}
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end">
                                        <RowActionsMenu
                                            @edit="openModal(service)"
                                            @delete="askDelete(service)"
                                        />
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
            <div
                v-else
                class="flex items-center justify-center gap-2 p-6 text-sm text-muted-foreground"
            >
                <DatabaseIcon class="size-4 shrink-0" />
                {{ $t('services.empty') }}
            </div>

            <ServiceModal
                v-model:open="modalOpen"
                :entry="editing"
                :currency-options="currencyOptions"
            />
            <ConfirmDialog
                :open="confirmOpen"
                :description="$t('services.delete_confirm', { name: deleteTarget?.name ?? '' })"
                @update:open="(value) => (confirmOpen = value)"
                @confirm="confirmDelete"
            />
        </main>
    </DefaultLayout>
</template>
