<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { PencilIcon, PlusIcon, Trash2Icon } from '@lucide/vue';
import { ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
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

function typeBadgeClass(type: string): string {
    return type === 'provider'
        ? 'border-amber-200 bg-amber-50 text-amber-600 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-400'
        : 'border-indigo-200 bg-indigo-50 text-indigo-600 dark:border-indigo-900 dark:bg-indigo-950 dark:text-indigo-400';
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
    <Head title="Contatos" />

    <DefaultLayout>
        <main class="flex flex-col gap-5 p-3 md:p-5">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-col">
                    <h1 class="text-lg font-semibold md:text-xl">Contatos</h1>
                    <span class="text-sm text-muted-foreground">
                        Fornecedores e clientes usados nas suas transações.
                    </span>
                </div>

                <Button
                    class="shrink-0 rounded-full"
                    size="icon-lg"
                    aria-label="Adicionar contato"
                    @click="openCreate"
                >
                    <PlusIcon />
                </Button>
            </div>

            <Card v-if="contacts.length > 0" class="gap-0 overflow-hidden p-0 md:p-0">
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nome</TableHead>
                                <TableHead>Tipo</TableHead>
                                <TableHead>Documento</TableHead>
                                <TableHead>Contato</TableHead>
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
                                    {{ contact.document ?? '—' }}
                                </TableCell>
                                <TableCell class="text-sm text-muted-foreground">
                                    <div class="flex flex-col">
                                        <span v-if="contact.email">{{ contact.email }}</span>
                                        <span v-if="contact.phone">{{ contact.phone }}</span>
                                        <span v-if="!contact.email && !contact.phone">—</span>
                                    </div>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="icon"
                                            aria-label="Editar contato"
                                            @click="openEdit(contact)"
                                        >
                                            <PencilIcon class="size-4" aria-hidden="true" />
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="icon"
                                            aria-label="Excluir contato"
                                            @click="deleteContact(contact)"
                                        >
                                            <Trash2Icon class="size-4" aria-hidden="true" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
            <div v-else class="p-6 text-center text-sm text-muted-foreground">empty</div>

            <ContactModal
                v-model:open="modalOpen"
                :entry="editingContact"
                :type-options="typeOptions"
            />

            <ConfirmDialog
                :open="confirmOpen"
                :description="`Excluir o contato “${deleteTarget?.name}”? Esta ação não pode ser desfeita.`"
                @update:open="(value) => (confirmOpen = value)"
                @confirm="confirmDelete"
            />
        </main>
    </DefaultLayout>
</template>
