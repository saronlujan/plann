<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { PencilIcon, PlusIcon, Trash2Icon } from '@lucide/vue';
import { ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
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
import { destroy as destroyTag } from '@/routes/settings/tags';
import TagModal from './components/TagModal.vue';

type Tag = { id: number; name: string; color: string };

defineProps<{ tags: Tag[] }>();

const modalOpen = ref(false);
const editing = ref<Tag | null>(null);
function openModal(tag: Tag | null): void {
    editing.value = tag;
    modalOpen.value = true;
}

const deleteTarget = ref<Tag | null>(null);
const confirmOpen = ref(false);
function askDelete(tag: Tag): void {
    deleteTarget.value = tag;
    confirmOpen.value = true;
}
function confirmDelete(): void {
    const target = deleteTarget.value;
    if (target === null) {
        return;
    }
    confirmOpen.value = false;
    router.delete(destroyTag(target.id).url, {
        preserveScroll: true,
        onFinish: () => (deleteTarget.value = null),
    });
}
</script>

<template>
    <Head :title="$t('settings.tags.title')" />

    <DefaultLayout>
        <main class="flex flex-col gap-5 p-3 md:p-5">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-col">
                    <h1 class="text-lg font-semibold md:text-xl">
                        {{ $t('settings.tags.title') }}
                    </h1>
                    <span class="text-sm text-muted-foreground">
                        {{ $t('settings.tags.subtitle') }}
                    </span>
                </div>
                <Button
                    size="icon-lg"
                    class="rounded-full"
                    :aria-label="$t('settings.tags.add')"
                    @click="openModal(null)"
                >
                    <PlusIcon />
                </Button>
            </div>

            <Card v-if="tags.length > 0" class="gap-0 overflow-hidden p-0 md:p-0">
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{{ $t('settings.tags.columns.name') }}</TableHead>
                                <TableHead class="text-right"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="tag in tags" :key="tag.id">
                                <TableCell class="font-medium">
                                    <span class="flex items-center gap-2">
                                        <span
                                            class="size-3 shrink-0 rounded-full"
                                            :style="{ backgroundColor: colorHex(tag.color) }"
                                        />
                                        {{ tag.name }}
                                    </span>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <Button
                                            variant="outline"
                                            size="icon"
                                            :aria-label="$t('common.actions.edit')"
                                            @click="openModal(tag)"
                                        >
                                            <PencilIcon class="size-4" />
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="icon"
                                            :aria-label="$t('common.actions.delete')"
                                            @click="askDelete(tag)"
                                        >
                                            <Trash2Icon class="size-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
            <div v-else class="p-6 text-center text-sm text-muted-foreground">
                {{ $t('common.state.empty') }}
            </div>

            <TagModal v-model:open="modalOpen" :entry="editing" />
            <ConfirmDialog
                :open="confirmOpen"
                :description="
                    $t('settings.tags.delete_confirm', { name: deleteTarget?.name ?? '' })
                "
                @update:open="(value) => (confirmOpen = value)"
                @confirm="confirmDelete"
            />
        </main>
    </DefaultLayout>
</template>
