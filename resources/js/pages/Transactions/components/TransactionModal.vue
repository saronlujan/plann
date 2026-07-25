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
import { store as storeTransaction } from '../../../routes/transactions';

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

const props = defineProps<{
    open: boolean;
    currencyOptions: CurrencyOption[];
    accountOptions: AccountOption[];
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const dialogOpen = computed({
    get: () => props.open,
    set: (value: boolean) => {
        emit('update:open', value);
    },
});

const today = new Date();
const localDate = new Date(today.getTime() - today.getTimezoneOffset() * 60000)
    .toISOString()
    .slice(0, 10);

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

const form = useForm({
    movement_type: 'expense',
    type: 'unique',
    description: '',
    currency_id: defaultCurrencyId.value,
    account_id: defaultAccountId.value,
    destination_account_id: '',
    effective_date: localDate,
    amount: '',
    adjustment_amount: '0',
    adjustment_month: '',
    interest_amount: '',
    installment_frequency: 'monthly',
    installments_total: '',
    installment_number: '',
    effective_until: '',
    attachment: null as File | null,
});

const hasAttachment = ref(false);

const transactionTypeOptions = [
    { label: 'Única', value: 'unique' },
    { label: 'Recorrente', value: 'recurring' },
    { label: 'Parcelada', value: 'installment' },
];

const transferLabel = 'Transferência';

const installmentFrequencyOptions = [
    { label: 'Semanal', value: 'weekly' },
    { label: 'Quinzenal', value: 'biweekly' },
    { label: 'Mensal', value: 'monthly' },
    { label: 'Bimestral', value: 'bimonthly' },
    { label: 'Semestral', value: 'semiannual' },
    { label: 'Anual', value: 'annual' },
];

const filteredAccountOptions = computed(() =>
    props.accountOptions.filter((account) => account.currency_id.toString() === form.currency_id),
);

const destinationAccountOptions = computed(() =>
    filteredAccountOptions.value.filter((account) => account.id.toString() !== form.account_id),
);

const isInstallmentType = computed(() => form.type === 'installment');
const isTransferMovement = computed(() => form.movement_type === 'transfer');
const accountLabel = computed(() => (isTransferMovement.value ? 'Conta de origem' : 'Conta'));

function closeModal(): void {
    emit('update:open', false);
    form.reset();
    form.clearErrors();
    hasAttachment.value = false;
}

function submitTransaction(): void {
    form.post(storeTransaction().url, {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
        },
    });
}

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
    { immediate: true },
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
                <DialogTitle>Nova transação</DialogTitle>
                <DialogDescription>
                    Preencha os dados básicos para criar uma transação.
                </DialogDescription>
            </DialogHeader>

            <Form class="space-y-5" @submit.prevent="submitTransaction">
                <div class="grid gap-4 md:grid-cols-2">
                    <FormGroup class="md:col-span-1">
                        <FormLabel>Tipo de transação</FormLabel>
                        <div class="grid grid-cols-3 gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                class="h-9 w-full justify-center border-red-200 px-3 py-1 text-sm text-red-700 hover:bg-red-50 hover:text-red-800 dark:border-red-900 dark:text-red-300 dark:hover:bg-red-950/40"
                                :class="
                                    form.movement_type === 'expense'
                                        ? 'border-red-500 bg-red-500/10 ring-2 ring-red-500/20'
                                        : ''
                                "
                                @click="form.movement_type = 'expense'"
                            >
                                <span class="inline-flex items-center gap-2">
                                    <span class="size-2 rounded-full bg-red-500"></span>
                                    Despesa
                                </span>
                            </Button>

                            <Button
                                type="button"
                                variant="outline"
                                class="h-9 w-full justify-center border-emerald-200 px-3 py-1 text-sm text-emerald-700 hover:bg-emerald-50 hover:text-emerald-800 dark:border-emerald-900 dark:text-emerald-300 dark:hover:bg-emerald-950/40"
                                :class="
                                    form.movement_type === 'income'
                                        ? 'border-emerald-500 bg-emerald-500/10 ring-2 ring-emerald-500/20'
                                        : ''
                                "
                                @click="form.movement_type = 'income'"
                            >
                                <span class="inline-flex items-center gap-2">
                                    <span class="size-2 rounded-full bg-emerald-500"></span>
                                    Receita
                                </span>
                            </Button>

                            <Button
                                type="button"
                                variant="outline"
                                class="h-9 w-full justify-center border-sky-200 px-3 py-1 text-sm text-sky-700 hover:bg-sky-50 hover:text-sky-800 dark:border-sky-900 dark:text-sky-300 dark:hover:bg-sky-950/40"
                                :class="
                                    form.movement_type === 'transfer'
                                        ? 'border-sky-500 bg-sky-500/10 ring-2 ring-sky-500/20'
                                        : ''
                                "
                                @click="form.movement_type = 'transfer'"
                            >
                                <span class="inline-flex items-center gap-2">
                                    <span class="size-2 rounded-full bg-sky-500"></span>
                                    {{ transferLabel }}
                                </span>
                            </Button>
                        </div>
                        <FormError :message="form.errors.movement_type" />
                    </FormGroup>

                    <FormGroup class="md:col-span-1">
                        <FormLabel>Tipo</FormLabel>
                        <NativeSelect v-model="form.type" name="type">
                            <NativeSelectOption value="">Selecione o tipo</NativeSelectOption>
                            <NativeSelectOption
                                v-for="option in transactionTypeOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </NativeSelectOption>
                        </NativeSelect>
                        <FormError :message="form.errors.type" />
                    </FormGroup>

                    <FormGroup>
                        <FormLabel>Moeda</FormLabel>
                        <NativeSelect v-model="form.currency_id" name="currency_id">
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
                        <FormLabel>{{ accountLabel }}</FormLabel>
                        <NativeSelect v-model="form.account_id" name="account_id">
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
                        <FormLabel>Conta de destino</FormLabel>
                        <NativeSelect
                            v-model="form.destination_account_id"
                            name="destination_account_id"
                        >
                            <NativeSelectOption value=""
                                >Selecione a conta de destino</NativeSelectOption
                            >
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
                        <FormLabel>Valor</FormLabel>
                        <Input
                            v-model="form.amount"
                            type="number"
                            step="0.01"
                            min="0"
                            name="amount"
                            placeholder="0.00"
                        />
                        <FormError :message="form.errors.amount" />
                    </FormGroup>

                    <FormGroup>
                        <FormLabel>Juros da conta</FormLabel>
                        <Input
                            v-model="form.interest_amount"
                            type="number"
                            step="0.01"
                            min="0"
                            name="interest_amount"
                            placeholder="0.00"
                        />
                        <FormError :message="form.errors.interest_amount" />
                    </FormGroup>

                    <FormGroup class="md:col-span-2">
                        <FormLabel>Descrição</FormLabel>
                        <Input
                            v-model="form.description"
                            type="text"
                            name="description"
                            placeholder="Describe the transaction"
                        />
                        <FormError :message="form.errors.description" />
                    </FormGroup>

                    <FormGroup class="md:col-span-2">
                        <div
                            class="flex items-center justify-between gap-3 rounded-lg border border-dashed px-3 py-2"
                        >
                            <div class="space-y-0.5">
                                <FormLabel class="text-sm">Adicionar anexo?</FormLabel>
                                <p class="text-xs text-muted-foreground">
                                    Marque para anexar um arquivo a esta transação.
                                </p>
                            </div>
                            <Switch v-model="hasAttachment" />
                        </div>
                    </FormGroup>
                </div>

                <FormCard
                    v-if="isInstallmentType && !isTransferMovement"
                    title="Parcelamento"
                    subtitle="Informe a quantidade de parcelas e o período de cobrança."
                >
                    <div class="grid gap-4 md:grid-cols-2">
                        <FormGroup>
                            <FormLabel>Quantidade de parcelas</FormLabel>
                            <Input
                                v-model="form.installments_total"
                                type="number"
                                min="1"
                                name="installments_total"
                                placeholder="12"
                            />
                            <FormError :message="form.errors.installments_total" />
                        </FormGroup>

                        <FormGroup>
                            <FormLabel>Período</FormLabel>
                            <NativeSelect
                                v-model="form.installment_frequency"
                                name="installment_frequency"
                            >
                                <NativeSelectOption value=""
                                    >Selecione o período</NativeSelectOption
                                >
                                <NativeSelectOption
                                    v-for="option in installmentFrequencyOptions"
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
                        <FormLabel>Arquivo</FormLabel>
                        <Input
                            :model-value="form.attachment"
                            @update:model-value="(value) => (form.attachment = value)"
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
                    <Button type="button" variant="outline" @click="closeModal"> Cancelar </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Salvando...' : 'Salvar transação' }}
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
