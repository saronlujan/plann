<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { Info } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { CurrencyInput } from '@/components/ui/currency-input';
import DatePicker from '@/components/ui/date-picker/DatePicker.vue';
import {
    Dialog,
    DialogFooter,
    DialogHeader,
    DialogScrollContent,
    DialogTitle,
} from '@/components/ui/dialog';
import { Form, FormError, FormGroup, FormLabel } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { colorHex } from '@/lib/labelColors';
import { cn } from '@/lib/utils';
import transactions from '@/routes/transactions';
import type {
    AccountOption,
    CategoryOption,
    CurrencyOption,
    Option,
    TagOption,
    TransactionEntry,
} from '../types';
import TagsSelect from './TagsSelect.vue';

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

const page = usePage<{ auth?: { user?: { default_currency_id?: number | null } | null } }>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const dialogOpen = computed({
    get: () => props.open,
    set: (value: boolean) => emit('update:open', value),
});

const isEdit = computed(() => props.entry != null);

// Narrowest scope first: the wider the reach, the further down the list.
// Computed, not a plain array: setup runs before the locale messages land, so
// eager labels freeze as their own translation keys.
const recurrenceScopeOptions = computed<Option[]>(() => [
    { value: 'one', label: trans('transactions.recurrence_scope.one') },
    { value: 'forward', label: trans('transactions.recurrence_scope.forward') },
    { value: 'all', label: trans('transactions.recurrence_scope.all') },
]);

function todayIsoDate(): string {
    const now = new Date();

    return new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
}

// The user's preferred currency wins; fall back to the first active one when no
// preference is set or the preferred currency is no longer active.
const defaultCurrencyId = computed(() => {
    const preferred = page.props.auth?.user?.default_currency_id;
    const match =
        preferred == null
            ? undefined
            : props.currencyOptions.find((currency) => currency.id === preferred);

    return (match ?? props.currencyOptions[0])?.id.toString() ?? '';
});
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
            recurrence_scope: 'one',
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
        recurrence_scope: 'one',
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

// Becoming a transfer would need a second leg in another account, which an edit
// cannot create — the server rejects it, so the option is not offered either.
const availableMovementTypes = computed(() =>
    isEdit.value
        ? props.movementTypeOptions.filter((option) => option.value !== 'transfer')
        : props.movementTypeOptions,
);

// Drives the amount field: the symbol prefix, and how many decimals it takes.
const selectedCurrency = computed(() =>
    props.currencyOptions.find((currency) => currency.id.toString() === form.currency_id),
);

// A transfer names itself, so the field is optional there and the label says so.
const descriptionLabel = computed(() =>
    trans(
        isTransferMovement.value
            ? 'transactions.fields.description_optional'
            : 'transactions.fields.description',
    ),
);
const showInstallmentFields = computed(() => isInstallmentType.value && !isTransferMovement.value);

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

// Narrower than what the server accepts on purpose: receipts are photos and
// PDFs, and the hint promises exactly that.
const ATTACHMENT_ACCEPT = 'image/jpeg,image/png,image/webp,application/pdf';

// The file input stays hidden until asked for: most entries have no receipt, and
// an empty file field in every form is just noise.
const attachmentEnabled = ref(false);

function toggleAttachment(enabled: boolean): void {
    attachmentEnabled.value = enabled;

    if (!enabled) {
        form.attachment = null;
    }
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

        attachmentEnabled.value = false;
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

// Turning a series into a one-off is a change to the whole series, not to a
// single occurrence — the scope picker is hidden by then, so reset what it holds
// or the edit would silently create an override instead.
watch(
    () => form.type,
    (type) => {
        if (type !== 'recurring') {
            form.recurrence_scope = 'all';

            return;
        }

        form.recurrence_scope = 'one';
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
        <!--
            Scroll lives on the overlay: this form runs past the viewport on a
            laptop once the installment or recurrence fields open up.
        -->
        <DialogScrollContent
            class="gap-4 overflow-hidden border-none p-0 **:data-[slot=dialog-close]:text-white **:data-[slot=dialog-close]:hover:bg-white/20 sm:max-w-2xl"
        >
            <div :class="cn('px-6 pt-6 pb-4', movementHeaderClass)">
                <DialogHeader>
                    <DialogTitle class="text-2xl text-white">{{ dialogTitle }}</DialogTitle>
                </DialogHeader>

                <div
                    role="radiogroup"
                    :aria-label="$t('transactions.modal.movement_type_group')"
                    :class="cn('mt-3 grid gap-2', isEdit ? 'grid-cols-2' : 'grid-cols-3')"
                >
                    <button
                        v-for="option in availableMovementTypes"
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
                    <FormGroup class="md:col-span-2">
                        <FormLabel for="tx-description">{{ descriptionLabel }}</FormLabel>
                        <Input
                            id="tx-description"
                            v-model="form.description"
                            type="text"
                            name="description"
                            :placeholder="$t('transactions.placeholders.description')"
                        />
                        <FormError :message="form.errors.description" />
                    </FormGroup>

                    <FormGroup class="md:col-span-1">
                        <FormLabel for="tx-amount">{{
                            $t('transactions.fields.amount')
                        }}</FormLabel>
                        <CurrencyInput
                            id="tx-amount"
                            v-model="form.amount"
                            name="amount"
                            :symbol="selectedCurrency?.symbol"
                            :code="selectedCurrency?.code"
                            :placeholder="$t('transactions.placeholders.amount')"
                        />
                        <FormError :message="form.errors.amount" />
                    </FormGroup>

                    <!--
                        A transfer is always a single, already-settled movement: the
                        watcher pins form.type to 'unique', so the field has nothing to
                        offer and is hidden rather than shown disabled.
                    -->
                    <FormGroup v-if="!isTransferMovement" class="md:col-span-1">
                        <FormLabel for="tx-type">{{ $t('transactions.fields.type') }}</FormLabel>
                        <Select v-model="form.type">
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

                    <!--
                        Installment details sit inline right after the type that reveals
                        them. The wrapper spans the grid so the pair always shares a row
                        of its own instead of pairing up with whatever field follows.
                    -->
                    <div
                        v-if="showInstallmentFields"
                        class="grid gap-4 md:col-span-2 md:grid-cols-2"
                    >
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

                    <!--
                        Tinted like a notice, and placed right under the amount and
                        type: those are what people come back to edit, and the scope
                        decides what the edit lands on.
                    -->
                    <FormGroup
                        v-if="isEdit && isRecurringType"
                        class="rounded-lg border border-amber-200 bg-amber-50 p-4 md:col-span-2 dark:border-amber-900 dark:bg-amber-950"
                    >
                        <FormLabel
                            id="tx-recurrence-scope-label"
                            class="text-amber-700 dark:text-amber-400"
                        >
                            {{ $t('transactions.recurrence.title') }}
                        </FormLabel>
                        <!--
                            A radio group, not a select: which occurrences an edit
                            touches is consequential enough that all three choices
                            should be visible without opening anything.
                        -->
                        <RadioGroup
                            v-model="form.recurrence_scope"
                            aria-labelledby="tx-recurrence-scope-label"
                            class="gap-2"
                        >
                            <label
                                v-for="option in recurrenceScopeOptions"
                                :key="option.value"
                                class="flex cursor-pointer items-center gap-2.5 text-sm"
                            >
                                <RadioGroupItem :value="option.value" :aria-label="option.label" />
                                {{ option.label }}
                            </label>
                        </RadioGroup>
                        <FormError :message="form.errors.recurrence_scope" />
                    </FormGroup>

                    <!--
                        With a single active currency there is nothing to choose: the
                        form already defaults to it, so the field would only be a
                        read-only echo taking up a slot.
                    -->
                    <FormGroup v-if="currencyOptions.length > 1">
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

                    <!--
                        On a transfer the source account is forced to start a new row so
                        the destination always lands beside it, whatever precedes them.
                    -->
                    <FormGroup :class="isTransferMovement ? 'md:col-start-1' : undefined">
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

                    <!--
                        Interest is hidden for now: it only makes sense alongside the
                        extra fields that describe how it is charged. The form still
                        posts interest_amount (empty), so the backend is unchanged.
                    -->

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

                    <FormGroup class="md:col-span-2">
                        <div class="flex items-center gap-2.5">
                            <Switch
                                id="tx-attachment-toggle"
                                size="sm"
                                :model-value="attachmentEnabled"
                                @update:model-value="toggleAttachment"
                            />
                            <FormLabel for="tx-attachment-toggle" class="cursor-pointer">
                                {{ $t('transactions.fields.attachment') }}
                            </FormLabel>
                            <TooltipProvider>
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <button
                                            type="button"
                                            class="text-muted-foreground transition-colors hover:text-foreground"
                                            :aria-label="$t('transactions.hints.attachment')"
                                        >
                                            <Info class="size-4" />
                                        </button>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        {{ $t('transactions.hints.attachment') }}
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </div>

                        <Input
                            v-if="attachmentEnabled"
                            id="tx-attachment"
                            type="file"
                            name="attachment"
                            :accept="ATTACHMENT_ACCEPT"
                            class="cursor-pointer file:mr-3 file:cursor-pointer file:text-sm file:text-muted-foreground"
                            @update:model-value="
                                (value) => (form.attachment = value as File | null)
                            "
                        />
                        <FormError :message="form.errors.attachment" />
                    </FormGroup>

                    <!--
                        A recurring transaction runs open-endedly, so there is no end
                        date to ask for. effective_until stays in the payload as an
                        empty value and the projector treats null as "no end".
                    -->
                </div>

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
        </DialogScrollContent>
    </Dialog>
</template>
