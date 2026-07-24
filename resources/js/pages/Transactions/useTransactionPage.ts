import { router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';
import transactions from '@/routes/transactions';
import type {
    AccountOption,
    CurrencySummary,
    TransactionEntry,
    TransactionFormState,
    TransactionPageProps,
} from './types';

const createTransactionState = (): TransactionFormState => ({
    movement_type: 'expense',
    type: 'unique',
    recurrence_scope: 'all',
    occurrence_date: '',
    description: '',
    currency_id: '',
    account_id: '',
    effective_date: '',
    amount: '',
    adjustment_amount: '',
    adjustment_month: '',
    installment_frequency: 'monthly',
    installments_total: '',
    installment_number: '',
});

export function useTransactionPage(props: TransactionPageProps): {
    transactionForm: ReturnType<typeof useForm<TransactionFormState>>;
    selectedScheduleType: ReturnType<typeof computed<TransactionFormState['type']>>;
    filteredAccountOptions: ReturnType<typeof computed<AccountOption[]>>;
    reportCurrency: ReturnType<typeof computed<CurrencySummary>>;
    actualIncome: ReturnType<typeof computed<number>>;
    actualExpense: ReturnType<typeof computed<number>>;
    actualTotal: ReturnType<typeof computed<number>>;
    expectedIncome: ReturnType<typeof computed<number>>;
    expectedExpense: ReturnType<typeof computed<number>>;
    expectedTotal: ReturnType<typeof computed<number>>;
    isTransactionModalOpen: typeof isTransactionModalOpen;
    editingTransactionId: typeof editingTransactionId;
    openTransactionModal: () => void;
    openEditTransactionModal: (entry: TransactionEntry) => void;
    closeTransactionModal: () => void;
    submitTransaction: () => void;
    payTransaction: (transactionId: number) => void;
} {
    const isTransactionModalOpen = ref(false);
    const editingTransactionId = ref<number | null>(null);
    const isPrefillingTransactionForm = ref(false);

    const transactionForm = useForm(createTransactionState());

    const selectedScheduleType = computed(() => transactionForm.type);
    const selectedCurrencyId = computed(() => {
        const parsedCurrencyId = Number(transactionForm.currency_id);

        return Number.isNaN(parsedCurrencyId) ? null : parsedCurrencyId;
    });

    const filteredAccountOptions = computed(() => {
        if (selectedCurrencyId.value === null) {
            return props.accountOptions;
        }

        return props.accountOptions.filter(
            (account) => account.currency_id === selectedCurrencyId.value,
        );
    });

    const reportCurrency = computed<CurrencySummary>(() =>
        props.currencySummaries.find(
            (currency) => currency.code === 'USD',
        ) ??
        props.currencySummaries[0] ?? {
            code: 'USD',
            name: 'Currency',
            symbol: '$',
            entries: 0,
            total: '0.00',
        },
    );

    const reportEntries = computed(() =>
        props.entries.filter(
            (entry) => entry.currency_code === reportCurrency.value.code,
        ),
    );

    const actualEntries = computed(() =>
        reportEntries.value.filter(
            (entry) => entry.kind === 'unique' || entry.kind === 'adjustment',
        ),
    );

    function sumEntriesByMovement(
        sourceEntries: TransactionEntry[],
        movementType: 'income' | 'expense',
    ): number {
        return sourceEntries.reduce((total, entry) => {
            if (entry.movement_type !== movementType) {
                return total;
            }

            return total + Number(entry.amount);
        }, 0);
    }

    const actualIncome = computed(() =>
        sumEntriesByMovement(actualEntries.value, 'income'),
    );
    const actualExpense = computed(() =>
        sumEntriesByMovement(actualEntries.value, 'expense'),
    );
    const actualTotal = computed(() => actualIncome.value - actualExpense.value);

    const expectedIncome = computed(() =>
        sumEntriesByMovement(reportEntries.value, 'income'),
    );
    const expectedExpense = computed(() =>
        sumEntriesByMovement(reportEntries.value, 'expense'),
    );
    const expectedTotal = computed(
        () => expectedIncome.value - expectedExpense.value,
    );

    watch(isTransactionModalOpen, (isOpen) => {
        if (!isOpen) {
            transactionForm.clearErrors();
        }
    });

    watch(selectedCurrencyId, () => {
        if (isPrefillingTransactionForm.value) {
            return;
        }

        transactionForm.account_id = '';
    });

    function openTransactionModal(): void {
        editingTransactionId.value = null;
        Object.assign(transactionForm, createTransactionState());
        isTransactionModalOpen.value = true;
    }

    function openEditTransactionModal(entry: TransactionEntry): void {
        editingTransactionId.value = entry.transaction_id;
        isPrefillingTransactionForm.value = true;

        Object.assign(transactionForm, {
            movement_type: entry.movement_type,
            type: entry.schedule_type,
            recurrence_scope: 'all',
            occurrence_date: entry.date,
            description: entry.description,
            currency_id: String(entry.currency_id),
            account_id: entry.account_id === null ? '' : String(entry.account_id),
            effective_date: entry.effective_date,
            amount: entry.amount,
            adjustment_amount: entry.adjustment_amount,
            adjustment_month: entry.adjustment_month ?? '',
            installment_frequency: entry.installment_frequency ?? 'monthly',
            installments_total:
                entry.installments_total === null
                    ? ''
                    : String(entry.installments_total),
            installment_number:
                entry.installment_number === null
                    ? ''
                    : String(entry.installment_number),
        });

        nextTick(() => {
            isPrefillingTransactionForm.value = false;
            isTransactionModalOpen.value = true;
        });
    }

    function closeTransactionModal(): void {
        editingTransactionId.value = null;
        isPrefillingTransactionForm.value = false;
        Object.assign(transactionForm, createTransactionState());
        isTransactionModalOpen.value = false;
    }

    function submitTransaction(): void {
        const url =
            editingTransactionId.value === null
                ? '/transactions'
                : `/transactions/${editingTransactionId.value}`;

        const submitMethod =
            editingTransactionId.value === null
                ? transactionForm.post
                : transactionForm.patch;

        submitMethod(url, {
            preserveScroll: true,
            onSuccess: () => {
                closeTransactionModal();
            },
        });
    }

    function payTransaction(transactionId: number): void {
        router.patch(transactions.pay(transactionId).url, {}, {
            preserveScroll: true,
        });
    }

    return {
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
    };
}
