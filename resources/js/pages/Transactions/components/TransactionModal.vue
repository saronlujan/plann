<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
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
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { Switch } from '@/components/ui/switch';
import transactions from '@/routes/transactions';
import type { AccountOption, CurrencyOption, Option, TransactionEntry } from '../types';

type TransactionFormData = {
    movement_type: string;
    type: string;
    description: string;
    currency_id: string;
    account_id: string;
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
        entry?: TransactionEntry | null;
    }>(),
    {
        entry: null,
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
        props.accountOptions.find((account) => account.currency_id.toString() === firstCurrencyId)?.id.toString() ??
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
        movement_type: 'expense',
        type: 'unique',
        description: '',
        currency_id: defaultCurrencyId.value,
        account_id: defaultAccountId.value,
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
const hasAttachment = ref(false);

const filteredAccountOptions = computed(() =>
    props.accountOptions.filter((account) => account.currency_id.toString() === form.currency_id),
);

const destinationAccountOptions = computed(() =>
    filteredAccountOptions.value.filter((account) => account.id.toString() !== form.account_id),
);

const isInstallmentType = computed(() => form.type === 'installment');
const isRecurringType = computed(() => form.type === 'recurring');
const isTransferMovement = computed(() => form.movement_type === 'transfer');
const accountLabel = computed(() => (isTransferMovement.value ? 'Conta de origem' : 'Conta'));
const dialogTitle = computed(() => (isEdit.value ? 'Editar transação' : 'Nova transação'));

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
        form
            .transform((data) => ({ ...data, _method: 'patch' }))
            .post(transactions.update(props.entry.transaction_id).url, options);

        return;
    }

    form.transform((data) => data).post(transactions.store().url, options);
}

watch(
    () => [props.open, props.entry] as const,
    ([open]) => {
        if (!open) {
            return;
        }

        form.defaults(buildInitialValues());
        form.reset();
        form.clearErrors();
        hasAttachment.value = false;
    },
    { immediate: true },
);

watch(
    () => form.currency_id,
    () => {
        const nextAccountId = filteredAccountOptions.value[0]?.id.toString() ?? '';

        if (
            nextAccountId !== '' &&
            !filteredAccountOptions.value.some((account) => account.id.toString() === form.account_id)
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

watch(hasAttachment, (enabled) => {
    if (!enabled) {
        form.attachment = null;
    }
});
</script>

<template>
    <Dialog v-model:open="dialogOpen">
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>{{ dialogTitle }}</DialogTitle>
                <DialogDescription>
                    Preencha os dados básicos para {{ isEdit ? 'atualizar' : 'criar' }} uma transação.
                </DialogDescription>
            </DialogHeader>

            <Form class="space-y-5" @submit.prevent="submitTransaction">
                <div class="grid gap-4 md:grid-cols-2">
                    <FormGroup class="md:col-span-1">
                        <FormLabel>Tipo de transação</FormLabel>
                        <div
                            role="radiogroup"
                            aria-label="Tipo de transação"
                            class="grid grid-cols-3 gap-2"
                        >
                            <Button
                                v-for="option in movementTypeOptions"
                                :key="option.value"
                                type="button"
                                :variant="form.movement_type === option.value ? 'default' : 'outline'"
                                role="radio"
                                :aria-checked="form.movement_type === option.value"
                                :aria-label="option.label"
                                class="h-9 w-full justify-center px-3 py-1 text-sm"
                                @click="form.movement_type = option.value"
                            >
                                {{ option.label }}
                            </Button>
                        </div>
                        <FormError :message="form.errors.movement_type" />
                    </FormGroup>

                    <FormGroup class="md:col-span-1">
                        <FormLabel for="tx-type">Tipo</FormLabel>
                        <NativeSelect id="tx-type" v-model="form.type" name="type" :disabled="isTransferMovement">
                            <NativeSelectOption value="">Selecione o tipo</NativeSelectOption>
                            <NativeSelectOption
                                v-for="option in scheduleTypeOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </NativeSelectOption>
                        </NativeSelect>
                        <FormError :message="form.errors.type" />
                    </FormGroup>

                    <FormGroup>
                        <FormLabel for="tx-currency">Moeda</FormLabel>
                        <NativeSelect id="tx-currency" v-model="form.currency_id" name="currency_id">
                            <NativeSelectOption value="">Selecione a moeda</NativeSelectOption>
                            <NativeSelectOption
                                v-for="currency in currencyOptions"
                                :key="currency.id"
                                :value="currency.id.toString()"
                            >
                                {{ currency.code }} - {{ currency.name }}
                            </NativeSelectOption>
                        </NativeSelect>
                        <FormError :message="form.errors.currency_id" />
                    </FormGroup>

                    <FormGroup>
                        <FormLabel for="tx-account">{{ accountLabel }}</FormLabel>
                        <NativeSelect id="tx-account" v-model="form.account_id" name="account_id">
                            <NativeSelectOption value="">Selecione a conta</NativeSelectOption>
                            <NativeSelectOption
                                v-for="account in filteredAccountOptions"
                                :key="account.id"
                                :value="account.id.toString()"
                            >
                                {{ account.name }}
                            </NativeSelectOption>
                        </NativeSelect>
                        <FormError :message="form.errors.account_id" />
                    </FormGroup>

                    <FormGroup v-if="isTransferMovement">
                        <FormLabel for="tx-destination">Conta de destino</FormLabel>
                        <NativeSelect
                            id="tx-destination"
                            v-model="form.destination_account_id"
                            name="destination_account_id"
                        >
                            <NativeSelectOption value="">Selecione a conta de destino</NativeSelectOption>
                            <NativeSelectOption
                                v-for="account in destinationAccountOptions"
                                :key="account.id"
                                :value="account.id.toString()"
                            >
                                {{ account.name }}
                            </NativeSelectOption>
                        </NativeSelect>
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

                    <FormGroup v-if="isRecurringType" class="md:col-span-2">
                        <FormLabel for="tx-effective-until">Repetir até (opcional)</FormLabel>
                        <DatePicker
                            v-model="form.effective_until"
                            label="Repetir até"
                            hint="Deixe em branco para uma recorrência sem fim."
                        />
                        <FormError :message="form.errors.effective_until" />
                    </FormGroup>

                    <FormGroup class="md:col-span-2">
                        <div
                            class="flex items-center justify-between gap-3 rounded-lg border border-dashed px-3 py-2"
                        >
                            <div class="space-y-0.5">
                                <FormLabel for="tx-attachment-toggle" class="text-sm">Adicionar anexo?</FormLabel>
                                <p class="text-xs text-muted-foreground">
                                    Marque para anexar um arquivo a esta transação.
                                </p>
                            </div>
                            <Switch id="tx-attachment-toggle" v-model="hasAttachment" />
                        </div>
                    </FormGroup>
                </div>

                <FormCard
                    v-if="isEdit && isRecurringType"
                    title="Escopo da recorrência"
                    subtitle="Escolha quais ocorrências desta série serão afetadas."
                >
                    <FormGroup>
                        <FormLabel for="tx-recurrence-scope">Aplicar alteração a</FormLabel>
                        <NativeSelect id="tx-recurrence-scope" v-model="form.recurrence_scope" name="recurrence_scope">
                            <NativeSelectOption
                                v-for="option in recurrenceScopeOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </NativeSelectOption>
                        </NativeSelect>
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
                            <FormLabel for="tx-installments-total">Quantidade de parcelas</FormLabel>
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
                            <NativeSelect id="tx-frequency" v-model="form.installment_frequency" name="installment_frequency">
                                <NativeSelectOption value="">Selecione o período</NativeSelectOption>
                                <NativeSelectOption
                                    v-for="option in frequencyOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </NativeSelectOption>
                            </NativeSelect>
                            <FormError :message="form.errors.installment_frequency" />
                        </FormGroup>
                    </div>
                </FormCard>

                <FormCard
                    v-if="hasAttachment"
                    title="Anexo"
                    subtitle="Adicione um arquivo de apoio para esta transação."
                >
                    <FormGroup>
                        <FormLabel for="tx-attachment">Arquivo</FormLabel>
                        <Input
                            id="tx-attachment"
                            :model-value="form.attachment"
                            @update:model-value="(value) => (form.attachment = value as File | null)"
                            type="file"
                            name="attachment"
                            accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx"
                        />
                        <p class="text-xs text-muted-foreground">
                            {{ form.attachment?.name ?? 'Nenhum arquivo selecionado' }}
                        </p>
                        <FormError :message="form.errors.attachment" />
                    </FormGroup>
                </FormCard>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="closeModal">Cancelar</Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Salvando...' : 'Salvar transação' }}
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
