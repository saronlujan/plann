<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
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
    { value: 'all', label: trans('transactions.recurrence_scope.all') },
    { value: 'one', label: trans('transactions.recurrence_scope.one') },
    { value: 'forward', label: trans('transactions.recurrence_scope.forward') },
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

// Categories carry a type (income / expense / both). Show the ones matching the
// current movement plus dual-use ("both") categories. Transfers have no category.
const filteredCategoryOptions = computed(() =>
    props.categoryOptions.filter(
        (category) => category.type === form.movement_type || category.type === 'both',
    ),
);

// Keep the already-selected category visible even if its type no longer matches
// the movement (e.g. the category's type was changed after this entry was created),
// so editing never silently drops it.
const categorySelectOptions = computed(() => {
    const list = [...filteredCategoryOptions.value];
    const selected = props.categoryOptions.find(
        (category) => category.id.toString() === form.category_id,
    );

    if (selected && !list.some((category) => category.id === selected.id)) {
        list.push(selected);
    }

    return list;
});

const accountLabel = computed(() =>
    isTransferMovement.value
        ? trans('transactions.fields.source_account')
        : trans('transactions.fields.account'),
);
const dialogTitle = computed(() =>
    isEdit.value
        ? trans('transactions.modal.edit_title')
        : trans('transactions.modal.create_title'),
);

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
            class="gap-4 overflow-hidden border-none p-0 **:data-[slot=dialog-close]:text-white **:data-[slot=dialog-close]:hover:bg-white/20 sm:max-w-2xl"
        >
            <div :class="cn('px-6 pt-6 pb-4', movementHeaderClass)">
                <DialogHeader>
                    <DialogTitle class="text-2xl text-white">{{ dialogTitle }}</DialogTitle>
                </DialogHeader>

                <div
                    role="radiogroup"
                    :aria-label="$t('transactions.modal.movement_type_group')"
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
                        <FormLabel for="tx-type">{{ $t('transactions.fields.type') }}</FormLabel>
                        <Select v-model="form.type" :disabled="isTransferMovement">
                            <SelectTrigger id="tx-type">
                                <SelectValue :placeholder="$t('transactions.placeholders.type')" />
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
                        <FormLabel for="tx-currency">{{
                            $t('transactions.fields.currency')
                        }}</FormLabel>
                        <Select v-model="form.currency_id">
                            <SelectTrigger id="tx-currency">
                                <SelectValue
                                    :placeholder="$t('transactions.placeholders.currency')"
                                />
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
                                <SelectValue
                                    :placeholder="$t('transactions.placeholders.account')"
                                />
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
                        <FormLabel for="tx-destination">{{
                            $t('transactions.fields.destination_account')
                        }}</FormLabel>
                        <Select v-model="form.destination_account_id">
                            <SelectTrigger id="tx-destination">
                                <SelectValue
                                    :placeholder="
                                        $t('transactions.placeholders.destination_account')
                                    "
                                />
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
                        <DatePicker
                            v-model="form.effective_date"
                            :label="$t('transactions.fields.effective_date')"
                        />
                        <FormError :message="form.errors.effective_date" />
                    </FormGroup>

                    <FormGroup>
                        <FormLabel for="tx-amount">{{
                            $t('transactions.fields.amount')
                        }}</FormLabel>
                        <Input
                            id="tx-amount"
                            v-model="form.amount"
                            type="number"
                            step="0.01"
                            min="0"
                            name="amount"
                            :placeholder="$t('transactions.placeholders.amount')"
                        />
                        <FormError :message="form.errors.amount" />
                    </FormGroup>

                    <FormGroup>
                        <FormLabel for="tx-interest">{{
                            $t('transactions.fields.interest')
                        }}</FormLabel>
                        <Input
                            id="tx-interest"
                            v-model="form.interest_amount"
                            type="number"
                            step="0.01"
                            min="0"
                            name="interest_amount"
                            :placeholder="$t('transactions.placeholders.amount')"
                        />
                        <FormError :message="form.errors.interest_amount" />
                    </FormGroup>

                    <FormGroup class="md:col-span-2">
                        <FormLabel for="tx-description">{{
                            $t('transactions.fields.description')
                        }}</FormLabel>
                        <Input
                            id="tx-description"
                            v-model="form.description"
                            type="text"
                            name="description"
                            :placeholder="$t('transactions.placeholders.description')"
                        />
                        <FormError :message="form.errors.description" />
                    </FormGroup>

                    <FormGroup v-if="!isTransferMovement">
                        <FormLabel for="tx-category">{{
                            $t('transactions.fields.category')
                        }}</FormLabel>
                        <Select
                            v-model="form.category_id"
                            :disabled="categorySelectOptions.length === 0"
                        >
                            <SelectTrigger id="tx-category">
                                <SelectValue
                                    :placeholder="
                                        categorySelectOptions.length === 0
                                            ? $t('transactions.placeholders.no_categories')
                                            : $t('transactions.placeholders.category')
                                    "
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="category in categorySelectOptions"
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
                        <FormLabel>{{ $t('transactions.fields.tags') }}</FormLabel>
                        <TagsSelect
                            v-model="form.tags"
                            :options="tagOptions"
                            :disabled="tagOptions.length === 0"
                            :placeholder="
                                tagOptions.length === 0
                                    ? $t('transactions.placeholders.no_tags')
                                    : $t('transactions.placeholders.tags')
                            "
                        />
                        <FormError :message="form.errors.tags" />
                    </FormGroup>

                    <FormGroup v-if="isRecurringType" class="md:col-span-2">
                        <FormLabel for="tx-effective-until">{{
                            $t('transactions.fields.repeat_until')
                        }}</FormLabel>
                        <DatePicker
                            v-model="form.effective_until"
                            :label="$t('transactions.fields.repeat_until_short')"
                            :hint="$t('transactions.hints.repeat_until')"
                        />
                        <FormError :message="form.errors.effective_until" />
                    </FormGroup>
                </div>

                <FormCard
                    v-if="isEdit && isRecurringType"
                    :title="$t('transactions.recurrence.title')"
                    :subtitle="$t('transactions.recurrence.subtitle')"
                >
                    <FormGroup>
                        <FormLabel for="tx-recurrence-scope">{{
                            $t('transactions.fields.apply_change_to')
                        }}</FormLabel>
                        <Select v-model="form.recurrence_scope">
                            <SelectTrigger id="tx-recurrence-scope">
                                <SelectValue :placeholder="$t('transactions.placeholders.scope')" />
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
                    :title="$t('transactions.installment.title')"
                    :subtitle="$t('transactions.installment.subtitle')"
                >
                    <div class="grid gap-4 md:grid-cols-2">
                        <FormGroup>
                            <FormLabel for="tx-installments-total">{{
                                $t('transactions.fields.installments_total')
                            }}</FormLabel>
                            <Input
                                id="tx-installments-total"
                                v-model="form.installments_total"
                                type="number"
                                min="1"
                                max="600"
                                name="installments_total"
                                :placeholder="$t('transactions.placeholders.installments_total')"
                            />
                            <FormError :message="form.errors.installments_total" />
                        </FormGroup>

                        <FormGroup>
                            <FormLabel for="tx-frequency">{{
                                $t('transactions.fields.frequency')
                            }}</FormLabel>
                            <Select v-model="form.installment_frequency">
                                <SelectTrigger id="tx-frequency">
                                    <SelectValue
                                        :placeholder="$t('transactions.placeholders.frequency')"
                                    />
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
                    <Button type="button" variant="outline" @click="closeModal">{{
                        $t('common.actions.cancel')
                    }}</Button>
                    <Button type="submit" :disabled="form.processing">
                        {{
                            form.processing
                                ? $t('common.actions.saving')
                                : $t('common.actions.save')
                        }}
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
