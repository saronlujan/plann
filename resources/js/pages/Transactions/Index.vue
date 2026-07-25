<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import Card from '@/components/ui/card/Card.vue';
import DefaultLayout from '@/layouts/DefaultLayout.vue';
import TransactionModal from './components/TransactionModal.vue';

type CurrencyOption = {
    id: number;
    code: string;
    name: string;
    symbol: string;
};

type AccountOption = {
    id: number;
    name: string;
    currency_id: number;
};

type TransactionsPageProps = {
    currencyOptions: CurrencyOption[];
    accountOptions: AccountOption[];
};

const props = defineProps<TransactionsPageProps>();
const createTransactionOpen = ref(false);
</script>

<template>
    <Head title="Transactions" />

    <DefaultLayout>
        <main class="flex flex-col p-3 md:p-5">
            <div class="mt-2 mb-5 flex w-full md:mt-0">
                <div class="flex flex-1 items-center justify-between gap-5">
                    <div class="flex items-start gap-2">
                        <div class="flex flex-col">
                            <h1 class="text-lg font-semibold md:text-xl">Your Transactions</h1>
                            <span class="hidden text-sm text-zinc-400 md:block dark:text-zinc-500">
                                View and manage your transactions.
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <Button class="shrink-0" @click="createTransactionOpen = true">
                            Add transaction
                        </Button>
                        <TransactionModal
                            v-model:open="createTransactionOpen"
                            :currency-options="props.currencyOptions"
                            :account-options="props.accountOptions"
                        />
                    </div>
                </div>
            </div>

            <Card>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    Use the button above to start creating transactions. The list will come next.
                </p>
            </Card>
        </main>
    </DefaultLayout>
</template>
