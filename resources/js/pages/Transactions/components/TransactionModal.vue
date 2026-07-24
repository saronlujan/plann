<script setup lang="ts">
import { toRef } from 'vue';
import type { AccountOption, CurrencyOption, TransactionFormState } from '../types';

const props = defineProps<{
    isOpen: boolean;
    editingTransactionId: number | null;
    transactionForm: TransactionFormState & {
        errors: Record<string, string | undefined>;
        processing: boolean;
        clearErrors: () => void;
        post: (...args: any[]) => void;
        patch: (...args: any[]) => void;
    };
    selectedScheduleType: 'unique' | 'recurring' | 'installment';
    currencyOptions: CurrencyOption[];
    filteredAccountOptions: AccountOption[];
}>();

const transactionForm = toRef(props, 'transactionForm');

defineEmits<{
    close: [];
    submit: [];
}>();
</script>

<template>
    <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-4 py-8 backdrop-blur-sm"
    >
        <div
            class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-[28px] border border-white/10 bg-[#0d0d0f] p-6 shadow-[0_32px_100px_rgba(0,0,0,0.55)]"
        >
            <div class="flex items-start justify-between gap-4 border-b border-white/10 pb-4">
                <div>
                    <p class="text-xs font-semibold tracking-[0.28em] text-slate-400 uppercase">
                        New transaction
                    </p>
                    <h3 class="mt-1 text-2xl font-semibold text-white">
                        {{ editingTransactionId ? 'Editar lançamento' : 'Inserir lançamento' }}
                    </h3>
                </div>

                <button
                    type="button"
                    class="grid h-10 w-10 place-items-center rounded-full border border-white/10 bg-white/5 text-slate-200 transition hover:bg-white/10"
                    @click="$emit('close')"
                >
                    ✕
                </button>
            </div>

            <form class="mt-6 space-y-5" @submit.prevent="$emit('submit')">
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="space-y-2 md:col-span-2">
                        <p class="text-xs font-semibold tracking-[0.28em] text-slate-400 uppercase">
                            Description
                        </p>
                        <input
                            v-model="transactionForm.description"
                            type="text"
                            class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-white outline-none placeholder:text-slate-500 focus:border-emerald-500"
                            placeholder="Aluguel, salário, parcela..."
                        />
                    </label>

                    <label class="space-y-2">
                        <p class="text-xs font-semibold tracking-[0.28em] text-slate-400 uppercase">
                            Transaction type
                        </p>
                        <select
                            v-model="transactionForm.movement_type"
                            class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-white outline-none focus:border-emerald-500"
                        >
                            <option value="expense" class="bg-slate-900">Expense</option>
                            <option value="income" class="bg-slate-900">Income</option>
                        </select>
                    </label>

                    <label class="space-y-2">
                        <p class="text-xs font-semibold tracking-[0.28em] text-slate-400 uppercase">
                            Schedule
                        </p>
                        <select
                            v-model="transactionForm.type"
                            class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-white outline-none focus:border-emerald-500"
                        >
                            <option value="unique" class="bg-slate-900">Unique</option>
                            <option value="recurring" class="bg-slate-900">Recurring</option>
                            <option value="installment" class="bg-slate-900">Installment</option>
                        </select>
                    </label>

                    <label
                        v-if="editingTransactionId !== null && transactionForm.type === 'recurring'"
                        class="space-y-2 md:col-span-2"
                    >
                        <p class="text-xs font-semibold tracking-[0.28em] text-slate-400 uppercase">
                            Apply changes to
                        </p>
                        <select
                            v-model="transactionForm.recurrence_scope"
                            class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-white outline-none focus:border-emerald-500"
                        >
                            <option value="one" class="bg-slate-900">Only this occurrence</option>
                            <option value="forward" class="bg-slate-900">
                                This and following occurrences
                            </option>
                            <option value="all" class="bg-slate-900">All occurrences</option>
                        </select>
                    </label>

                    <label class="space-y-2">
                        <p class="text-xs font-semibold tracking-[0.28em] text-slate-400 uppercase">
                            Currency
                        </p>
                        <select
                            v-model="transactionForm.currency_id"
                            class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-white outline-none focus:border-emerald-500"
                        >
                            <option value="" class="bg-slate-900">Select currency</option>
                            <option
                                v-for="currency in currencyOptions"
                                :key="currency.id"
                                :value="currency.id"
                                class="bg-slate-900"
                            >
                                {{ currency.code }} - {{ currency.symbol }}
                            </option>
                        </select>
                    </label>

                    <label class="space-y-2">
                        <p class="text-xs font-semibold tracking-[0.28em] text-slate-400 uppercase">
                            Account
                        </p>
                        <select
                            v-model="transactionForm.account_id"
                            class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-white outline-none focus:border-emerald-500"
                        >
                            <option value="" class="bg-slate-900">Select account</option>
                            <option
                                v-for="account in filteredAccountOptions"
                                :key="account.id"
                                :value="account.id"
                                class="bg-slate-900"
                            >
                                {{ account.name }}
                            </option>
                        </select>
                    </label>

                    <label class="space-y-2">
                        <p class="text-xs font-semibold tracking-[0.28em] text-slate-400 uppercase">
                            Date
                        </p>
                        <input
                            v-model="transactionForm.effective_date"
                            type="date"
                            class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-white outline-none focus:border-emerald-500"
                        />
                    </label>

                    <input
                        v-if="editingTransactionId !== null"
                        v-model="transactionForm.occurrence_date"
                        type="hidden"
                        name="occurrence_date"
                    />

                    <label class="space-y-2">
                        <p class="text-xs font-semibold tracking-[0.28em] text-slate-400 uppercase">
                            Amount
                        </p>
                        <input
                            v-model="transactionForm.amount"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-white outline-none focus:border-emerald-500"
                        />
                    </label>

                    <div
                        v-if="
                            selectedScheduleType === 'recurring' ||
                            selectedScheduleType === 'installment'
                        "
                        class="grid gap-4 md:col-span-2 md:grid-cols-2"
                    >
                        <label class="space-y-2">
                            <p
                                class="text-xs font-semibold tracking-[0.28em] text-slate-400 uppercase"
                            >
                                Adjustment month
                            </p>
                            <input
                                v-model="transactionForm.adjustment_month"
                                type="date"
                                class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-white outline-none focus:border-emerald-500"
                            />
                        </label>

                        <label class="space-y-2">
                            <p
                                class="text-xs font-semibold tracking-[0.28em] text-slate-400 uppercase"
                            >
                                Adjustment amount
                            </p>
                            <input
                                v-model="transactionForm.adjustment_amount"
                                type="number"
                                step="0.01"
                                min="0"
                                class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-white outline-none focus:border-emerald-500"
                            />
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-white/10 pt-4">
                    <button
                        type="button"
                        class="rounded-full border border-white/10 bg-white/5 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-white/10"
                        @click="$emit('close')"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="rounded-full bg-emerald-500 px-5 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-emerald-400"
                        :disabled="transactionForm.processing"
                    >
                        {{ editingTransactionId ? 'Update transaction' : 'Save transaction' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
