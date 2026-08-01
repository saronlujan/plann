<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { DatabaseIcon, PlusIcon } from '@lucide/vue';
import { ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
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
import { destroy as destroyContact } from '@/routes/contacts';
import ContactModal from './components/ContactModal.vue';

type Option = { value: string; label: string };
type Contact = {
    id: number;
    name: string;
    type: string;
    email: string | null;
    phone: string | null;
    document: string | null;
    notes: string | null;
};

const props = defineProps<{
    contacts: Contact[];
    typeOptions: Option[];
}>();

function typeLabel(type: string): string {
    return props.typeOptions.find((option) => option.value === type)?.label ?? type;
}

const typeBadgeClasses: Record<string, string> = {
    provider:
        'border-amber-200 bg-amber-50 text-amber-600 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-400',
    client: 'border-indigo-200 bg-indigo-50 text-indigo-600 dark:border-indigo-900 dark:bg-indigo-950 dark:text-indigo-400',
    partner:
        'border-emerald-200 bg-emerald-50 text-emerald-600 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-400',
    platform:
        'border-sky-200 bg-sky-50 text-sky-600 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-400',
};

function typeBadgeClass(type: string): string {
    return typeBadgeClasses[type] ?? typeBadgeClasses.client;
}

const modalOpen = ref(false);
const editingContact = ref<Contact | null>(null);

function openCreate(): void {
    editingContact.value = null;
    modalOpen.value = true;
}

function openEdit(contact: Contact): void {
    editingContact.value = contact;
    modalOpen.value = true;
}

const deleteTarget = ref<Contact | null>(null);
const confirmOpen = ref(false);

function deleteContact(contact: Contact): void {
    deleteTarget.value = contact;
    confirmOpen.value = true;
}

function confirmDelete(): void {
    const target = deleteTarget.value;

    if (target === null) {
        return;
    }

    confirmOpen.value = false;
    router.delete(destroyContact(target.id).url, {
        preserveScroll: true,
        onFinish: () => (deleteTarget.value = null),
    });
}
</script>

<template>
    <Head :title="$t('contacts.title')" />

    <DefaultLayout>
        <main class="flex flex-col gap-5 p-3 md:p-5">
            <PageHeader :title="$t('contacts.title')" :subtitle="$t('contacts.subtitle')">
                <Button
                    class="shrink-0 rounded-full"
                    size="icon-lg"
                    :aria-label="$t('contacts.add')"
                    @click="openCreate"
                >
                    <PlusIcon />
                </Button>
            </PageHeader>

            <Card v-if="contacts.length > 0" class="gap-0 overflow-hidden p-0 md:p-0">
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{{ $t('contacts.table.name') }}</TableHead>
                                <TableHead>{{ $t('contacts.table.type') }}</TableHead>
                                <TableHead>{{ $t('contacts.table.document') }}</TableHead>
                                <TableHead>{{ $t('contacts.table.contact') }}</TableHead>
                                <TableHead class="text-right"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="contact in contacts" :key="contact.id">
                                <TableCell class="font-medium">{{ contact.name }}</TableCell>
                                <TableCell>
                                    <Badge
                                        variant="outline"
                                        class="rounded-full"
                                        :class="typeBadgeClass(contact.type)"
                                    >
                                        {{ typeLabel(contact.type) }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-sm text-muted-foreground">
                                    {{ contact.document ?? $t('common.state.none') }}
                                </TableCell>
                                <TableCell class="text-sm text-muted-foreground">
                                    <div class="flex flex-col">
                                        <span v-if="contact.email">{{ contact.email }}</span>
                                        <span v-if="contact.phone">{{ contact.phone }}</span>
                                        <span v-if="!contact.email && !contact.phone">{{
                                            $t('common.state.none')
                                        }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end">
                                        <RowActionsMenu
                                            @edit="openEdit(contact)"
                                            @delete="deleteContact(contact)"
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
                {{ $t('contacts.empty') }}
            </div>

            <ContactModal
                v-model:open="modalOpen"
                :entry="editingContact"
                :type-options="typeOptions"
            />

            <ConfirmDialog
                :open="confirmOpen"
                :description="$t('contacts.delete_confirm', { name: deleteTarget?.name ?? '' })"
                @update:open="(value) => (confirmOpen = value)"
                @confirm="confirmDelete"
            />
        </main>
    </DefaultLayout>
</template>
