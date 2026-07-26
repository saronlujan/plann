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
import { destroy as destroyAccount } from '@/routes/settings/accounts';
import AccountModal from './components/AccountModal.vue';

type Option = { value: string; label: string };
type Account = {
    id: number;
    name: string;
    currency_id: number;
    currency_code: string;
    balance: string;
};

defineProps<{ accounts: Account[]; currencyOptions: Option[] }>();

function formatBalance(account: Account): string {
    const value = Number.parseFloat(account.balance);
    const amount = new Intl.NumberFormat('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number.isFinite(value) ? value : 0);

    return `${account.currency_code} ${amount}`;
}

const modalOpen = ref(false);
const editing = ref<Account | null>(null);
function openModal(account: Account | null): void {
    editing.value = account;
    modalOpen.value = true;
}

const deleteTarget = ref<Account | null>(null);
const confirmOpen = ref(false);
function askDelete(account: Account): void {
    deleteTarget.value = account;
    confirmOpen.value = true;
}
function confirmDelete(): void {
    const target = deleteTarget.value;
    if (target === null) {
        return;
    }
    confirmOpen.value = false;
    router.delete(destroyAccount(target.id).url, {
        preserveScroll: true,
        onFinish: () => (deleteTarget.value = null),
    });
}
</script>

<template>
    <Head title="Contas" />

    <DefaultLayout>
        <main class="flex flex-col gap-5 p-3 md:p-5">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-col">
                    <h1 class="text-lg font-semibold md:text-xl">Contas</h1>
                    <span class="text-sm text-muted-foreground"> Contas em moedas ativas. </span>
                </div>
                <Button
                    size="icon-lg"
                    class="rounded-full"
                    aria-label="Adicionar conta"
                    :disabled="currencyOptions.length === 0"
                    @click="openModal(null)"
                >
                    <PlusIcon />
                </Button>
            </div>

            <Card v-if="accounts.length > 0" class="gap-0 overflow-hidden p-0 md:p-0">
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nome</TableHead>
                                <TableHead>Moeda</TableHead>
                                <TableHead class="text-right">Saldo</TableHead>
                                <TableHead class="text-right"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="account in accounts" :key="account.id">
                                <TableCell class="font-medium">{{ account.name }}</TableCell>
                                <TableCell class="text-sm text-muted-foreground">{{
                                    account.currency_code
                                }}</TableCell>
                                <TableCell class="text-right whitespace-nowrap">{{
                                    formatBalance(account)
                                }}</TableCell>
                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <Button
                                            variant="outline"
                                            size="icon"
                                            aria-label="Editar"
                                            @click="openModal(account)"
                                        >
                                            <PencilIcon class="size-4" />
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="icon"
                                            aria-label="Excluir"
                                            @click="askDelete(account)"
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
            <div v-else class="p-6 text-center text-sm text-muted-foreground">empty</div>

            <AccountModal
                v-model:open="modalOpen"
                :entry="editing"
                :currency-options="currencyOptions"
            />
            <ConfirmDialog
                :open="confirmOpen"
                :description="`Excluir “${deleteTarget?.name}”? Esta ação não pode ser desfeita.`"
                @update:open="(value) => (confirmOpen = value)"
                @confirm="confirmDelete"
            />
        </main>
    </DefaultLayout>
</template>
