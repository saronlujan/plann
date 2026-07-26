<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { Button } from '@/components/ui/button';
import DatePicker from '@/components/ui/date-picker/DatePicker.vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Form, FormCard, FormError, FormGroup, FormLabel } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { colorHex } from '@/lib/labelColors';
import { cn } from '@/lib/utils';
import transactions from '@/routes/transactions';
import TagsSelect from './TagsSelect.vue';
import type {
    AccountOption,
    CategoryOption,
    CurrencyOption,
    Option,
    TagOption,
    TransactionEntry,
} from '../types';

type TransactionFormData = {
    movement_type: string;
    type: string;
    description: string;
    currency_id: string;
    account_id: string;
    category_id: string;
    tags: number[];
    destination_account_id: string;
    effective_date: string;
    effective_until: string;
    amount: string;
    interest_amount: string;
    installment_frequency: string;
    installments_total: string;
    recurrence_scope: string;
    occurrence_date: string;
    attachment: File | null;
};

const props = withDefaults(
    defineProps<{
        open: boolean;
        currencyOptions: CurrencyOption[];
        accountOptions: AccountOption[];
        movementTypeOptions: Option[];
        scheduleTypeOptions: Option[];
        frequencyOptions: Option[];
        categoryOptions: CategoryOption[];
        tagOptions: TagOption[];
        entry?: TransactionEntry | null;
        initialMovementType?: string;
    }>(),
    {
        entry: null,
        initialMovementType: 'expense',
    },
);

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const dialogOpen = computed({
    get: () => props.open,
    set: (value: boolean) => emit('update:open', value),
});

const isEdit = computed(() => props.entry != null);

const recurrenceScopeOptions: Option[] = [
    { value: 'all', label: 'Toda a série' },
    { value: 'one', label: 'Somente esta ocorrência' },
    { value: 'forward', label: 'Esta e as futuras' },
];

function todayIsoDate(): string {
    const now = new Date();

    return new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
}

const defaultCurrencyId = computed(() => props.currencyOptions[0]?.id.toString() ?? '');
const defaultAccountId = computed(() => {
    const firstCurrencyId = defaultCurrencyId.value;

    return (
        props.accountOptions
            .find((account) => account.currency_id.toString() === firstCurrencyId)
            ?.id.toString() ??
        props.accountOptions[0]?.id.toString() ??
        ''
    );
});

function buildInitialValues(): TransactionFormData {
    const entry = props.entry;

    if (entry) {
        return {
            movement_type: entry.movement_type,
            type: entry.schedule_type,
            description: entry.description,
            currency_id: entry.currency_id.toString(),
            account_id: entry.account_id?.toString() ?? '',
            category_id: entry.category_id?.toString() ?? '',
            tags: [...(entry.tag_ids ?? [])],
            destination_account_id: '',
            effective_date: entry.effective_date,
            effective_until: entry.effective_until ?? '',
            amount: entry.amount,
            interest_amount: '',
            installment_frequency: entry.installment_frequency ?? 'monthly',
            installments_total: entry.installments_total?.toString() ?? '',
            recurrence_scope: 'all',
            occurrence_date: entry.date,
            attachment: null as File | null,
        };
    }

    return {
        movement_type: props.initialMovementType,
        type: 'unique',
        description: '',
        currency_id: defaultCurrencyId.value,
        account_id: defaultAccountId.value,
        category_id: '',
        tags: [],
        destination_account_id: '',
        effective_date: todayIsoDate(),
        effective_until: '',
        amount: '',
        interest_amount: '',
        installment_frequency: 'monthly',
        installments_total: '',
        recurrence_scope: 'all',
        occurrence_date: todayIsoDate(),
        attachment: null as File | null,
    };
}

const form = useForm(buildInitialValues());

const filteredAccountOptions = computed(() =>
    props.accountOptions.filter((account) => account.currency_id.toString() === form.currency_id),
);

const destinationAccountOptions = computed(() =>
    filteredAccountOptions.value.filter((account) => account.id.toString() !== form.account_id),
);

const isInstallmentType = computed(() => form.type === 'installment');
const isRecurringType = computed(() => form.type === 'recurring');
const isTransferMovement = computed(() => form.movement_type === 'transfer');

// Categories carry an income/expense type, so only show the ones matching the
// current movement. Transfers have no category.
const filteredCategoryOptions = computed(() =>
    props.categoryOptions.filter((category) => category.type === form.movement_type),
);

const accountLabel = computed(() => (isTransferMovement.value ? 'Conta de origem' : 'Conta'));
const dialogTitle = computed(() => (isEdit.value ? 'Edit Transaction' : 'New Transaction'));

// Solid color at the top of the modal, one per movement type. Softer (darker,
// less saturated) shades in dark mode so it doesn't glare.
const movementHeaderClass = computed(() => {
    switch (form.movement_type) {
        case 'income':
            return 'bg-emerald-500 dark:bg-emerald-800';
        case 'transfer':
            return 'bg-sky-500 dark:bg-sky-800';
        default:
            return 'bg-red-500 dark:bg-red-800';
    }
});

function closeModal(): void {
    emit('update:open', false);
}

function submitTransaction(): void {
    const options = {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => closeModal(),
        onError: () => {
            // Validation errors are surfaced inline through form.errors.
        },
    };

    if (isEdit.value && props.entry) {
        form.transform((data) => ({ ...data, _method: 'patch' })).post(
            transactions.update(props.entry.transaction_id).url,
            options,
        );

        return;
    }

    form.transform((data) => data).post(transactions.store().url, options);
}

watch(
    () => [props.open, props.entry, props.initialMovementType] as const,
    ([open]) => {
        if (!open) {
            return;
        }

        form.defaults(buildInitialValues());
        form.reset();
        form.clearErrors();
    },
    { immediate: true },
);

watch(
    () => form.currency_id,
    () => {
        const nextAccountId = filteredAccountOptions.value[0]?.id.toString() ?? '';

        if (
            nextAccountId !== '' &&
            !filteredAccountOptions.value.some(
                (account) => account.id.toString() === form.account_id,
            )
        ) {
            form.account_id = nextAccountId;
        }
    },
);

watch(
    () => [form.movement_type, form.account_id, form.currency_id],
    () => {
        if (isTransferMovement.value && form.type !== 'unique') {
            form.type = 'unique';
        }

        if (!isTransferMovement.value) {
            form.destination_account_id = '';

            return;
        }

        const nextDestinationAccountId = destinationAccountOptions.value[0]?.id.toString() ?? '';

        if (
            nextDestinationAccountId !== '' &&
            !destinationAccountOptions.value.some(
                (account) => account.id.toString() === form.destination_account_id,
            )
        ) {
            form.destination_account_id = nextDestinationAccountId;
        }
    },
);

// Drop a selected category that no longer matches the chosen movement type.
watch(
    () => form.movement_type,
    () => {
        if (
            form.category_id !== '' &&
            !filteredCategoryOptions.value.some(
                (category) => category.id.toString() === form.category_id,
            )
        ) {
            form.category_id = '';
        }
    },
);
</script>

<template>
    <Dialog v-model:open="dialogOpen">
        <DialogContent
            class="overflow-hidden border-none p-0 **:data-[slot=dialog-close]:text-white **:data-[slot=dialog-close]:hover:bg-white/20 sm:max-w-2xl"
        >
            <div :class="cn('px-6 pt-6 pb-4', movementHeaderClass)">
                <DialogHeader>
                    <DialogTitle class="text-2xl text-white">{{ dialogTitle }}</DialogTitle>
                </DialogHeader>

                <div
                    role="radiogroup"
                    aria-label="Tipo de transação"
                    class="mt-3 grid grid-cols-3 gap-2"
                >
                    <button
                        v-for="option in movementTypeOptions"
                        :key="option.value"
                        type="button"
                        role="radio"
                        :aria-checked="form.movement_type === option.value"
                        :aria-label="option.label"
                        :class="
                            cn(
                                'rounded-md px-3 py-1.5 text-sm font-medium transition',
                                form.movement_type === option.value
                                    ? 'bg-white text-zinc-900 shadow-sm'
                                    : 'bg-white/20 text-white hover:bg-white/30',
                            )
                        "
                        @click="form.movement_type = option.value"
                    >
                        {{ option.label }}
                    </button>
                </div>
                <FormError :message="form.errors.movement_type" class="mt-1 text-white/90" />
            </div>

            <Form class="space-y-5 px-6 pb-6" @submit.prevent="submitTransaction">
                <div class="grid gap-4 md:grid-cols-2">
                    <FormGroup class="md:col-span-1">
                        <FormLabel for="tx-type">Tipo</FormLabel>
                        <Select v-model="form.type" :disabled="isTransferMovement">
                            <SelectTrigger id="tx-type">
                                <SelectValue placeholder="Selecione o tipo" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in scheduleTypeOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <FormError :message="form.errors.type" />
                    </FormGroup>

                    <FormGroup>
                        <FormLabel for="tx-currency">Moeda</FormLabel>
                        <Select v-model="form.currency_id">
                            <SelectTrigger id="tx-currency">
                                <SelectValue placeholder="Selecione a moeda" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="currency in currencyOptions"
                                    :key="currency.id"
                                    :value="currency.id.toString()"
                                >
                                    {{ currency.code }} - {{ currency.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <FormError :message="form.errors.currency_id" />
                    </FormGroup>

                    <FormGroup>
                        <FormLabel for="tx-account">{{ accountLabel }}</FormLabel>
                        <Select v-model="form.account_id">
                            <SelectTrigger id="tx-account">
                                <SelectValue placeholder="Selecione a conta" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="account in filteredAccountOptions"
                                    :key="account.id"
                                    :value="account.id.toString()"
                                >
                                    {{ account.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <FormError :message="form.errors.account_id" />
                    </FormGroup>

                    <FormGroup v-if="isTransferMovement">
                        <FormLabel for="tx-destination">Conta de destino</FormLabel>
                        <Select v-model="form.destination_account_id">
                            <SelectTrigger id="tx-destination">
                                <SelectValue placeholder="Selecione a conta de destino" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="account in destinationAccountOptions"
                                    :key="account.id"
                                    :value="account.id.toString()"
                                >
                                    {{ account.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <FormError :message="form.errors.destination_account_id" />
                    </FormGroup>

                    <FormGroup>
                        <DatePicker v-model="form.effective_date" label="Data efetiva" />
                        <FormError :message="form.errors.effective_date" />
                    </FormGroup>

                    <FormGroup>
                        <FormLabel for="tx-amount">Valor</FormLabel>
                        <Input
                            id="tx-amount"
                            v-model="form.amount"
                            type="number"
                            step="0.01"
                            min="0"
                            name="amount"
                            placeholder="0,00"
                        />
                        <FormError :message="form.errors.amount" />
                    </FormGroup>

                    <FormGroup>
                        <FormLabel for="tx-interest">Juros da conta</FormLabel>
                        <Input
                            id="tx-interest"
                            v-model="form.interest_amount"
                            type="number"
                            step="0.01"
                            min="0"
                            name="interest_amount"
                            placeholder="0,00"
                        />
                        <FormError :message="form.errors.interest_amount" />
                    </FormGroup>

                    <FormGroup class="md:col-span-2">
                        <FormLabel for="tx-description">Descrição</FormLabel>
                        <Input
                            id="tx-description"
                            v-model="form.description"
                            type="text"
                            name="description"
                            placeholder="Descreva a transação"
                        />
                        <FormError :message="form.errors.description" />
                    </FormGroup>

                    <FormGroup v-if="!isTransferMovement">
                        <FormLabel for="tx-category">Categoria</FormLabel>
                        <Select
                            v-model="form.category_id"
                            :disabled="filteredCategoryOptions.length === 0"
                        >
                            <SelectTrigger id="tx-category">
                                <SelectValue
                                    :placeholder="
                                        filteredCategoryOptions.length === 0
                                            ? 'Nenhuma categoria cadastrada'
                                            : 'Selecione a categoria'
                                    "
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="category in filteredCategoryOptions"
                                    :key="category.id"
                                    :value="category.id.toString()"
                                >
                                    <span class="flex items-center gap-2">
                                        <span
                                            class="size-2.5 rounded-full"
                                            :style="{ backgroundColor: colorHex(category.color) }"
                                        />
                                        {{ category.name }}
                                    </span>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <FormError :message="form.errors.category_id" />
                    </FormGroup>

                    <FormGroup v-if="!isTransferMovement">
                        <FormLabel>Tags</FormLabel>
                        <TagsSelect
                            v-model="form.tags"
                            :options="tagOptions"
                            :disabled="tagOptions.length === 0"
                            :placeholder="
                                tagOptions.length === 0
                                    ? 'Nenhuma tag cadastrada'
                                    : 'Selecione tags'
                            "
                        />
                        <FormError :message="form.errors.tags" />
                    </FormGroup>

                    <FormGroup v-if="isRecurringType" class="md:col-span-2">
                        <FormLabel for="tx-effective-until">Repetir até (opcional)</FormLabel>
                        <DatePicker
                            v-model="form.effective_until"
                            label="Repetir até"
                            hint="Deixe em branco para uma recorrência sem fim."
                        />
                        <FormError :message="form.errors.effective_until" />
                    </FormGroup>
                </div>

                <FormCard
                    v-if="isEdit && isRecurringType"
                    title="Escopo da recorrência"
                    subtitle="Escolha quais ocorrências desta série serão afetadas."
                >
                    <FormGroup>
                        <FormLabel for="tx-recurrence-scope">Aplicar alteração a</FormLabel>
                        <Select v-model="form.recurrence_scope">
                            <SelectTrigger id="tx-recurrence-scope">
                                <SelectValue placeholder="Selecione o escopo" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in recurrenceScopeOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <FormError :message="form.errors.recurrence_scope" />
                    </FormGroup>
                </FormCard>

                <FormCard
                    v-if="isInstallmentType && !isTransferMovement"
                    title="Parcelamento"
                    subtitle="Informe a quantidade de parcelas e o período de cobrança."
                >
                    <div class="grid gap-4 md:grid-cols-2">
                        <FormGroup>
                            <FormLabel for="tx-installments-total"
                                >Quantidade de parcelas</FormLabel
                            >
                            <Input
                                id="tx-installments-total"
                                v-model="form.installments_total"
                                type="number"
                                min="1"
                                max="600"
                                name="installments_total"
                                placeholder="12"
                            />
                            <FormError :message="form.errors.installments_total" />
                        </FormGroup>

                        <FormGroup>
                            <FormLabel for="tx-frequency">Período</FormLabel>
                            <Select v-model="form.installment_frequency">
                                <SelectTrigger id="tx-frequency">
                                    <SelectValue placeholder="Selecione o período" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in frequencyOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <FormError :message="form.errors.installment_frequency" />
                        </FormGroup>
                    </div>
                </FormCard>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="closeModal">Cancel</Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save' }}
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
