<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { DatabaseIcon, PlusIcon } from '@lucide/vue';
import { ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import { Badge } from '@/components/ui/badge';
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
import { destroy as destroyCategory } from '@/routes/settings/categories';
import CategoryModal from './components/CategoryModal.vue';

type Option = { value: string; label: string };
type Category = { id: number; name: string; type: string; color: string };

const props = defineProps<{ categories: Category[]; categoryTypeOptions: Option[] }>();

function typeLabel(type: string): string {
    return props.categoryTypeOptions.find((option) => option.value === type)?.label ?? type;
}

function typeBadgeClass(type: string): string {
    if (type === 'income') {
        return 'border-emerald-200 bg-emerald-50 text-emerald-600 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-400';
    }

    if (type === 'both') {
        return 'border-sky-200 bg-sky-50 text-sky-600 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-400';
    }

    return 'border-red-200 bg-red-50 text-red-600 dark:border-red-900 dark:bg-red-950 dark:text-red-400';
}

const modalOpen = ref(false);
const editing = ref<Category | null>(null);
function openModal(category: Category | null): void {
    editing.value = category;
    modalOpen.value = true;
}

const deleteTarget = ref<Category | null>(null);
const confirmOpen = ref(false);
function askDelete(category: Category): void {
    deleteTarget.value = category;
    confirmOpen.value = true;
}
function confirmDelete(): void {
    const target = deleteTarget.value;
    if (target === null) {
        return;
    }
    confirmOpen.value = false;
    router.delete(destroyCategory(target.id).url, {
        preserveScroll: true,
        onFinish: () => (deleteTarget.value = null),
    });
}
</script>

<template>
    <Head :title="$t('settings.categories.title')" />

    <DefaultLayout>
        <main class="flex flex-col gap-5 p-3 md:p-5">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-col">
                    <h1 class="text-lg font-semibold md:text-xl">
                        {{ $t('settings.categories.title') }}
                    </h1>
                    <span class="text-sm text-muted-foreground">
                        {{ $t('settings.categories.subtitle') }}
                    </span>
                </div>
                <Button
                    size="icon-lg"
                    class="rounded-full"
                    :aria-label="$t('settings.categories.add')"
                    @click="openModal(null)"
                >
                    <PlusIcon />
                </Button>
            </div>

            <Card v-if="categories.length > 0" class="gap-0 overflow-hidden p-0 md:p-0">
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{{ $t('settings.categories.columns.name') }}</TableHead>
                                <TableHead>{{ $t('settings.categories.columns.type') }}</TableHead>
                                <TableHead class="text-right"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="category in categories" :key="category.id">
                                <TableCell class="font-medium">
                                    <span class="flex items-center gap-2">
                                        <span
                                            class="size-3 shrink-0 rounded-full"
                                            :style="{ backgroundColor: colorHex(category.color) }"
                                        />
                                        {{ category.name }}
                                    </span>
                                </TableCell>
                                <TableCell>
                                    <Badge
                                        variant="outline"
                                        class="rounded-full"
                                        :class="typeBadgeClass(category.type)"
                                    >
                                        {{ typeLabel(category.type) }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end">
                                        <RowActionsMenu
                                            @edit="openModal(category)"
                                            @delete="askDelete(category)"
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
                {{ $t('settings.categories.empty') }}
            </div>

            <CategoryModal
                v-model:open="modalOpen"
                :entry="editing"
                :type-options="categoryTypeOptions"
            />
            <ConfirmDialog
                :open="confirmOpen"
                :description="
                    $t('settings.categories.delete_confirm', { name: deleteTarget?.name ?? '' })
                "
                @update:open="(value) => (confirmOpen = value)"
                @confirm="confirmDelete"
            />
        </main>
    </DefaultLayout>
</template>
