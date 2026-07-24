<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import TransactionModal from './components/TransactionModal.vue';
import TransactionsFilters from './components/TransactionsFilters.vue';
import TransactionsHeader from './components/TransactionsHeader.vue';
import TransactionsSummary from './components/TransactionsSummary.vue';
import TransactionsTable from './components/TransactionsTable.vue';
import type { TransactionPageProps } from './types';
import { useTransactionPage } from './useTransactionPage';

const props = defineProps<TransactionPageProps>();
const {
    period,
    periodDisplay,
    periodPrevious,
    periodNext,
    filters,
    kindOptions,
    currencyOptions,
    entries,
    totals,
} = props;

const {
    transactionForm,
    selectedScheduleType,
    filteredAccountOptions,
    reportCurrency,
    actualIncome,
    actualExpense,
    actualTotal,
    expectedIncome,
    expectedExpense,
    expectedTotal,
    isTransactionModalOpen,
    editingTransactionId,
    openTransactionModal,
    openEditTransactionModal,
    closeTransactionModal,
    submitTransaction,
    payTransaction,
} = useTransactionPage(props);

function formatMoney(amount: number): string {
    return `${reportCurrency.value.symbol}${amount.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;
}
</script>

<template>
    <Head title="Lançamentos" />

    <main class="min-h-screen bg-[#070707] text-slate-100">
        <div class="mx-auto flex min-h-screen w-full max-w-280 flex-col gap-6 px-4 py-4 lg:px-6">
            <TransactionsHeader :on-add-transaction="openTransactionModal" />

            <TransactionsFilters
                :period="period"
                :period-display="periodDisplay"
                :period-previous="periodPrevious"
                :period-next="periodNext"
                :filters="filters"
                :kind-options="kindOptions"
            />

            <TransactionsTable
                :entries="entries"
                :period-display="periodDisplay"
                :adjustments-count="totals.adjustments"
                @edit="openEditTransactionModal"
                @pay="payTransaction"
            />

            <TransactionsSummary
                :currency-code="reportCurrency.code"
                :period-display="periodDisplay"
                :actual-income="formatMoney(actualIncome)"
                :actual-expense="formatMoney(actualExpense)"
                :actual-total="formatMoney(actualTotal)"
                :expected-income="formatMoney(expectedIncome)"
                :expected-expense="formatMoney(expectedExpense)"
                :expected-total="formatMoney(expectedTotal)"
            />

            <TransactionModal
                :is-open="isTransactionModalOpen"
                :editing-transaction-id="editingTransactionId"
                :transaction-form="transactionForm"
                :selected-schedule-type="selectedScheduleType"
                :currency-options="currencyOptions"
                :filtered-account-options="filteredAccountOptions"
                @close="closeTransactionModal"
                @submit="submitTransaction"
            />
        </div>
    </main>
</template>
