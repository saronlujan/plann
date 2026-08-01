<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { Check } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { CurrencyInput } from '@/components/ui/currency-input';
import DatePicker from '@/components/ui/date-picker/DatePicker.vue';
import { Form, FormError, FormGroup, FormLabel } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import DefaultLayout from '@/layouts/DefaultLayout.vue';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { store as storeAccount } from '@/routes/accounts';
import { store as storeCategory } from '@/routes/categories';
import { store as storeContact } from '@/routes/contacts';
import { store as storeTag } from '@/routes/tags';
import transactions from '@/routes/transactions';
import ColorPopover from './components/ColorPopover.vue';

type Option = { value: string; label: string };
type CurrencyOption = Option & { code: string; symbol: string };
type ColorOption = { value: string; label: string };

const props = defineProps<{
    completed: Record<string, boolean>;
    currentStep: string;
    plan: string;
    userName: string | null;
    currencyOptions: CurrencyOption[];
    defaultCurrencyId: string;
    accountKindOptions: Option[];
    categoryTypeOptions: Option[];
    movementTypeOptions: Option[];
    scheduleTypeOptions: Option[];
    frequencyOptions: Option[];
    colorOptions: ColorOption[];
    contactTypeOptions: Option[];
    accountOptions: (Option & { currency_id: number })[];
    categoryOptions: (Option & { type: string })[];
    tagOptions: { value: number; label: string }[];
    created: Record<string, string | null>;
}>();

const STEPS = ['account', 'category', 'tag', 'contact', 'transaction'] as const;

const stepIndex = computed(() => STEPS.indexOf(props.currentStep as (typeof STEPS)[number]));

const nextStep = computed(() => STEPS[stepIndex.value + 1] ?? null);

/**
 * A finished step opens on what it produced, not on a blank form.
 *
 * Coming back to one and retyping the same thing either created a second record
 * (accounts carry no unique rule) or tripped the unique name on categories and
 * tags — which read as "it will not submit".
 */
const creatingAnother = ref(false);

const stepDone = computed(() => props.completed[props.currentStep] === true);

const createdName = computed(() => props.created[props.currentStep] ?? null);

/** Jumping to a specific step — going back, or skipping an optional one. */
function goToStep(step: string): void {
    router.get('', { step }, { preserveScroll: true, preserveState: false, replace: true });
}

// The one piece of copy written twice: one tier is organising their own
// money, the other is running a business.
const planIntro = computed(() =>
    trans(props.plan === 'pro' ? 'onboarding.intro.pro' : 'onboarding.intro.basic'),
);

function todayIsoDate(): string {
    const now = new Date();

    return new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
}

// Every step posts to its module's own endpoint: one set of validation rules,
// and one place where they are kept in sync.
//
// Saving already moves on — the modules answer with back(), which returns to the
// onboarding with no step named, and the server then resolves the first
// unfinished one. A step reached by skipping is the exception: its name stays in
// the URL, so it has to be nudged along by hand.
function advanceFrom(step: string) {
    return {
        preserveScroll: true,
        onSuccess: (): void => {
            if (props.currentStep !== step) {
                return;
            }

            const next = STEPS[STEPS.indexOf(step as (typeof STEPS)[number]) + 1];

            if (next) {
                goToStep(next);
            } else {
                router.visit(dashboard().url);
            }
        },
    };
}

const accountForm = useForm({
    name: '',
    kind: props.accountKindOptions[0]?.value ?? 'account',
    currency_id: props.defaultCurrencyId || (props.currencyOptions[0]?.value ?? ''),
});

const categoryForm = useForm({
    name: '',
    type: props.categoryTypeOptions[0]?.value ?? 'expense',
    color: props.colorOptions[0]?.value ?? 'zinc',
});

const tagForm = useForm({
    name: '',
    color: props.colorOptions[0]?.value ?? 'zinc',
});

const contactForm = useForm({
    name: '',
    type: props.contactTypeOptions[0]?.value ?? 'provider',
});

const paidOptions = computed(() => [
    { value: true, label: trans('common.state.yes') },
    { value: false, label: trans('common.state.no') },
]);

const transactionForm = useForm({
    movement_type: props.movementTypeOptions[0]?.value ?? 'expense',
    type: 'unique',
    installment_frequency: 'monthly',
    installments_total: '',
    description: '',
    amount: '',
    currency_id: '',
    account_id: '',
    category_id: '',
    effective_date: todayIsoDate(),
    paid: true,
});

// The first entry belongs to the account just created, so the form points at it
// rather than asking again.
const firstAccount = computed(() => props.accountOptions[0]);

const selectedCurrency = computed(() =>
    props.currencyOptions.find(
        (currency) => Number(currency.value) === firstAccount.value?.currency_id,
    ),
);

// Instalments carry a count and a period; the other schedules do not, so the
// fields appear only when they mean something.
const isInstallment = computed(() => transactionForm.type === 'installment');

const categoriesForMovement = computed(() =>
    props.categoryOptions.filter((category) => category.type === transactionForm.movement_type),
);

function submitAccount(): void {
    accountForm.post(storeAccount().url, advanceFrom('account'));
}

function submitCategory(): void {
    categoryForm.post(storeCategory().url, advanceFrom('category'));
}

function submitTag(): void {
    tagForm.post(storeTag().url, advanceFrom('tag'));
}

function submitContact(): void {
    contactForm.post(storeContact().url, advanceFrom('contact'));
}

function submitTransaction(): void {
    transactionForm
        .transform((data) => ({
            ...data,
            currency_id: String(firstAccount.value?.currency_id ?? ''),
            account_id: firstAccount.value?.value ?? '',
        }))
        .post(transactions.store().url, {
            preserveScroll: true,
            onSuccess: () => router.visit(dashboard().url),
        });
}
</script>

<template>
    <Head :title="$t('onboarding.title')" />

    <DefaultLayout>
        <main class="mx-auto flex w-full max-w-2xl flex-col gap-5 p-3 md:p-5">
            <!--
                A trail, not a menu: a finished step can be revisited, an unreached
                one cannot, because each depends on the one before it.
            -->
            <ol class="flex items-start justify-center gap-2">
                <li v-for="(step, index) in STEPS" :key="step" class="flex items-start gap-2">
                    <!-- Number and name in one column so the labels line up. -->
                    <span class="flex w-16 flex-col items-center gap-1.5 sm:w-20">
                        <button
                            type="button"
                            :disabled="!completed[step] && index !== stepIndex"
                            :aria-current="step === currentStep ? 'step' : undefined"
                            :class="
                                cn(
                                    'flex size-8 items-center justify-center rounded-full border text-xs font-medium transition',
                                    step === currentStep
                                        ? 'border-zinc-950 bg-zinc-950 text-white dark:border-zinc-100 dark:bg-zinc-100 dark:text-zinc-950'
                                        : completed[step]
                                          ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400'
                                          : 'border-input text-muted-foreground',
                                )
                            "
                            @click="goToStep(step)"
                        >
                            <Check v-if="completed[step] && step !== currentStep" class="size-4" />
                            <span v-else>{{ index + 1 }}</span>
                        </button>
                        <span
                            :class="
                                cn(
                                    'text-center text-[11px] leading-tight',
                                    step === currentStep
                                        ? 'font-medium text-foreground'
                                        : 'text-muted-foreground',
                                )
                            "
                        >
                            {{ $t(`onboarding.steps.${step}`) }}
                        </span>
                    </span>
                    <span
                        v-if="index < STEPS.length - 1"
                        class="mt-4 h-px w-4 bg-border sm:w-6"
                        aria-hidden="true"
                    />
                </li>
            </ol>

            <Card class="relative overflow-hidden">
                <CardContent class="min-h-64 gap-5 p-5 md:p-6">
                    <!-- ALREADY DONE -->
                    <template v-if="stepDone && !creatingAnother">
                        <div class="flex items-start gap-3">
                            <span
                                class="flex size-9 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400"
                            >
                                <Check class="size-5" />
                            </span>
                            <div class="flex min-w-0 flex-col gap-1">
                                <h2 class="font-medium">{{ $t('onboarding.done.title') }}</h2>
                                <p class="text-sm leading-relaxed text-muted-foreground">
                                    {{
                                        $t('onboarding.done.description', {
                                            name: createdName ?? '',
                                        })
                                    }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <Button v-if="nextStep" type="button" @click="goToStep(nextStep)">
                                {{ $t('onboarding.actions.continue') }}
                            </Button>
                            <Link
                                v-else
                                :href="dashboard().url"
                                class="inline-flex h-9 items-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground"
                            >
                                {{ $t('onboarding.actions.finish') }}
                            </Link>
                            <button
                                type="button"
                                class="text-sm text-muted-foreground underline underline-offset-4 hover:text-foreground"
                                @click="creatingAnother = true"
                            >
                                {{ $t('onboarding.actions.create_another') }}
                            </button>
                        </div>
                    </template>

                    <!-- ACCOUNT -->
                    <template v-else-if="currentStep === 'account'">
                        <!-- The pitch rides on the first step: a screen that only
                             greets is a screen the user has to click through. -->
                        <div class="flex flex-col gap-2 border-b border-border pb-5">
                            <h2 class="text-lg font-semibold">
                                {{ $t('onboarding.intro.title', { name: userName ?? '' }) }}
                            </h2>
                            <p class="text-sm leading-relaxed text-muted-foreground">
                                {{ planIntro }}
                            </p>
                        </div>

                        <div class="flex flex-col gap-1">
                            <h3 class="font-medium">{{ $t('onboarding.account.title') }}</h3>
                            <p class="text-sm leading-relaxed text-muted-foreground">
                                {{ $t('onboarding.account.description') }}
                            </p>
                            <p class="text-sm text-muted-foreground/80">
                                {{ $t('onboarding.account.example') }}
                            </p>
                        </div>

                        <Form class="gap-4" @submit.prevent="submitAccount">
                            <FormGroup>
                                <FormLabel for="ob-account-name">
                                    {{ $t('onboarding.account.name') }}
                                </FormLabel>
                                <Input
                                    id="ob-account-name"
                                    v-model="accountForm.name"
                                    :placeholder="$t('onboarding.account.name_placeholder')"
                                />
                                <FormError :message="accountForm.errors.name" />
                            </FormGroup>

                            <FormGroup>
                                <FormLabel for="ob-account-kind">
                                    {{ $t('onboarding.account.kind') }}
                                </FormLabel>
                                <Select v-model="accountForm.kind">
                                    <SelectTrigger id="ob-account-kind">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="option in accountKindOptions"
                                            :key="option.value"
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <FormError :message="accountForm.errors.kind" />
                            </FormGroup>

                            <!-- One currency needs no choosing; the plan already made it. -->
                            <FormGroup v-if="currencyOptions.length > 1">
                                <FormLabel for="ob-account-currency">
                                    {{ $t('onboarding.account.currency') }}
                                </FormLabel>
                                <Select v-model="accountForm.currency_id">
                                    <SelectTrigger id="ob-account-currency">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="option in currencyOptions"
                                            :key="option.value"
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <FormError :message="accountForm.errors.currency_id" />
                            </FormGroup>

                            <div class="flex items-center gap-3">
                                <Button type="submit" :disabled="accountForm.processing">
                                    {{ $t('onboarding.actions.create_account') }}
                                </Button>
                            </div>
                        </Form>
                    </template>

                    <!-- CATEGORY -->
                    <template v-else-if="currentStep === 'category'">
                        <div class="flex flex-col gap-1">
                            <h3 class="font-medium">{{ $t('onboarding.category.title') }}</h3>
                            <p class="text-sm leading-relaxed text-muted-foreground">
                                {{ $t('onboarding.category.description') }}
                            </p>
                            <p class="text-sm text-muted-foreground/80">
                                {{ $t('onboarding.category.example') }}
                            </p>
                        </div>

                        <Form class="gap-4" @submit.prevent="submitCategory">
                            <FormGroup>
                                <FormLabel for="ob-category-name">
                                    {{ $t('onboarding.category.name') }}
                                </FormLabel>
                                <div class="flex items-center gap-2">
                                    <Input
                                        id="ob-category-name"
                                        v-model="categoryForm.name"
                                        class="flex-1"
                                        :placeholder="$t('onboarding.category.name_placeholder')"
                                    />
                                    <ColorPopover v-model="categoryForm.color" />
                                </div>
                                <span class="text-xs text-muted-foreground">
                                    {{ $t('onboarding.color_help') }}
                                </span>
                                <FormError :message="categoryForm.errors.name" />
                                <FormError :message="categoryForm.errors.color" />
                            </FormGroup>

                            <div class="flex flex-col gap-4">
                                <FormGroup>
                                    <FormLabel for="ob-category-type">
                                        {{ $t('onboarding.category.type') }}
                                    </FormLabel>
                                    <Select v-model="categoryForm.type">
                                        <SelectTrigger id="ob-category-type">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="option in categoryTypeOptions"
                                                :key="option.value"
                                                :value="option.value"
                                            >
                                                {{ option.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <FormError :message="categoryForm.errors.type" />
                                </FormGroup>
                            </div>

                            <div class="flex items-center gap-3">
                                <Button type="submit" :disabled="categoryForm.processing">
                                    {{ $t('onboarding.actions.create_category') }}
                                </Button>
                                <button
                                    type="button"
                                    class="text-sm text-muted-foreground underline underline-offset-4 hover:text-foreground"
                                    @click="goToStep('tag')"
                                >
                                    {{ $t('onboarding.actions.skip') }}
                                </button>
                            </div>
                        </Form>
                    </template>

                    <!-- TAG -->
                    <template v-else-if="currentStep === 'tag'">
                        <div class="flex flex-col gap-1">
                            <h3 class="font-medium">{{ $t('onboarding.tag.title') }}</h3>
                            <p class="text-sm leading-relaxed text-muted-foreground">
                                {{ $t('onboarding.tag.description') }}
                            </p>
                            <p class="text-sm text-muted-foreground/80">
                                {{ $t('onboarding.tag.example') }}
                            </p>
                        </div>

                        <Form class="gap-4" @submit.prevent="submitTag">
                            <div class="flex flex-col gap-4">
                                <FormGroup>
                                    <FormLabel for="ob-tag-name">
                                        {{ $t('onboarding.tag.name') }}
                                    </FormLabel>
                                    <div class="flex items-center gap-2">
                                        <Input
                                            id="ob-tag-name"
                                            v-model="tagForm.name"
                                            class="flex-1"
                                            :placeholder="$t('onboarding.tag.name_placeholder')"
                                        />
                                        <ColorPopover v-model="tagForm.color" />
                                    </div>
                                    <span class="text-xs text-muted-foreground">
                                        {{ $t('onboarding.color_help') }}
                                    </span>
                                    <FormError :message="tagForm.errors.name" />
                                    <FormError :message="tagForm.errors.color" />
                                </FormGroup>
                            </div>

                            <div class="flex items-center gap-3">
                                <Button type="submit" :disabled="tagForm.processing">
                                    {{ $t('onboarding.actions.create_tag') }}
                                </Button>
                                <button
                                    type="button"
                                    class="text-sm text-muted-foreground underline underline-offset-4 hover:text-foreground"
                                    @click="goToStep('contact')"
                                >
                                    {{ $t('onboarding.actions.skip') }}
                                </button>
                            </div>
                        </Form>
                    </template>

                    <!-- CONTACT -->
                    <template v-else-if="currentStep === 'contact'">
                        <div class="flex flex-col gap-1">
                            <h3 class="font-medium">{{ $t('onboarding.contact.title') }}</h3>
                            <p class="text-sm leading-relaxed text-muted-foreground">
                                {{ $t('onboarding.contact.description') }}
                            </p>
                            <p class="text-sm text-muted-foreground/80">
                                {{ $t('onboarding.contact.example') }}
                            </p>
                        </div>

                        <Form class="gap-4" @submit.prevent="submitContact">
                            <FormGroup>
                                <FormLabel for="ob-contact-name">
                                    {{ $t('onboarding.contact.name') }}
                                </FormLabel>
                                <Input
                                    id="ob-contact-name"
                                    v-model="contactForm.name"
                                    :placeholder="$t('onboarding.contact.name_placeholder')"
                                />
                                <FormError :message="contactForm.errors.name" />
                            </FormGroup>

                            <FormGroup>
                                <FormLabel for="ob-contact-type">
                                    {{ $t('onboarding.contact.type') }}
                                </FormLabel>
                                <Select v-model="contactForm.type">
                                    <SelectTrigger id="ob-contact-type">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="option in contactTypeOptions"
                                            :key="option.value"
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <FormError :message="contactForm.errors.type" />
                            </FormGroup>

                            <div class="flex items-center gap-3">
                                <Button type="submit" :disabled="contactForm.processing">
                                    {{ $t('onboarding.actions.create_contact') }}
                                </Button>
                                <button
                                    type="button"
                                    class="text-sm text-muted-foreground underline underline-offset-4 hover:text-foreground"
                                    @click="goToStep('transaction')"
                                >
                                    {{ $t('onboarding.actions.skip') }}
                                </button>
                            </div>
                        </Form>
                    </template>

                    <!-- TRANSACTION -->
                    <template v-else>
                        <div class="flex flex-col gap-1">
                            <h3 class="font-medium">{{ $t('onboarding.transaction.title') }}</h3>
                            <p class="text-sm leading-relaxed text-muted-foreground">
                                {{ $t('onboarding.transaction.description') }}
                            </p>
                            <p class="text-sm text-muted-foreground/80">
                                {{ $t('onboarding.transaction.example') }}
                            </p>
                        </div>

                        <Form class="gap-4" @submit.prevent="submitTransaction">
                            <FormGroup>
                                <FormLabel for="ob-tx-description">
                                    {{ $t('onboarding.transaction.name') }}
                                </FormLabel>
                                <Input
                                    id="ob-tx-description"
                                    v-model="transactionForm.description"
                                    :placeholder="$t('onboarding.transaction.name_placeholder')"
                                />
                                <FormError :message="transactionForm.errors.description" />
                            </FormGroup>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <FormGroup>
                                    <FormLabel for="ob-tx-movement">
                                        {{ $t('onboarding.transaction.movement') }}
                                    </FormLabel>
                                    <Select v-model="transactionForm.movement_type">
                                        <SelectTrigger id="ob-tx-movement">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="option in movementTypeOptions"
                                                :key="option.value"
                                                :value="option.value"
                                            >
                                                {{ option.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <FormError :message="transactionForm.errors.movement_type" />
                                </FormGroup>

                                <FormGroup>
                                    <FormLabel for="ob-tx-amount">
                                        {{ $t('onboarding.transaction.amount') }}
                                    </FormLabel>
                                    <CurrencyInput
                                        id="ob-tx-amount"
                                        v-model="transactionForm.amount"
                                        :symbol="selectedCurrency?.symbol"
                                        :code="selectedCurrency?.code"
                                    />
                                    <FormError :message="transactionForm.errors.amount" />
                                </FormGroup>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <FormGroup>
                                    <FormLabel for="ob-tx-schedule">
                                        {{ $t('onboarding.transaction.schedule') }}
                                    </FormLabel>
                                    <Select v-model="transactionForm.type">
                                        <SelectTrigger id="ob-tx-schedule">
                                            <SelectValue />
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
                                    <FormError :message="transactionForm.errors.type" />
                                </FormGroup>

                                <FormGroup>
                                    <FormLabel id="ob-tx-paid-label">
                                        {{ $t('onboarding.transaction.paid') }}
                                    </FormLabel>
                                    <div
                                        role="radiogroup"
                                        aria-labelledby="ob-tx-paid-label"
                                        class="inline-flex h-9 w-full items-center justify-center rounded-lg bg-muted p-[3px] text-muted-foreground"
                                    >
                                        <button
                                            v-for="option in paidOptions"
                                            :key="option.label"
                                            type="button"
                                            role="radio"
                                            :aria-checked="transactionForm.paid === option.value"
                                            :class="
                                                cn(
                                                    'inline-flex h-full flex-1 items-center justify-center rounded-md border border-transparent px-2 py-1 text-sm font-medium transition-[color,box-shadow]',
                                                    transactionForm.paid === option.value
                                                        ? 'bg-background text-foreground shadow-sm dark:border-input dark:bg-input/30'
                                                        : 'hover:text-foreground',
                                                )
                                            "
                                            @click="transactionForm.paid = option.value"
                                        >
                                            {{ option.label }}
                                        </button>
                                    </div>
                                    <FormError :message="transactionForm.errors.paid" />
                                </FormGroup>
                            </div>

                            <div v-if="isInstallment" class="grid gap-4 sm:grid-cols-2">
                                <FormGroup>
                                    <FormLabel for="ob-tx-installments">
                                        {{ $t('onboarding.transaction.installments_total') }}
                                    </FormLabel>
                                    <Input
                                        id="ob-tx-installments"
                                        v-model="transactionForm.installments_total"
                                        type="number"
                                        min="1"
                                        max="600"
                                    />
                                    <FormError
                                        :message="transactionForm.errors.installments_total"
                                    />
                                </FormGroup>

                                <FormGroup>
                                    <FormLabel for="ob-tx-frequency">
                                        {{ $t('onboarding.transaction.frequency') }}
                                    </FormLabel>
                                    <Select v-model="transactionForm.installment_frequency">
                                        <SelectTrigger id="ob-tx-frequency">
                                            <SelectValue />
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
                                    <FormError
                                        :message="transactionForm.errors.installment_frequency"
                                    />
                                </FormGroup>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <FormGroup>
                                    <DatePicker
                                        v-model="transactionForm.effective_date"
                                        :label="$t('onboarding.transaction.date')"
                                    />
                                    <FormError :message="transactionForm.errors.effective_date" />
                                </FormGroup>

                                <FormGroup v-if="categoriesForMovement.length > 0">
                                    <FormLabel for="ob-tx-category">
                                        {{ $t('onboarding.transaction.category') }}
                                    </FormLabel>
                                    <Select v-model="transactionForm.category_id">
                                        <SelectTrigger id="ob-tx-category">
                                            <SelectValue
                                                :placeholder="
                                                    $t(
                                                        'onboarding.transaction.category_placeholder',
                                                    )
                                                "
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="option in categoriesForMovement"
                                                :key="option.value"
                                                :value="option.value"
                                            >
                                                {{ option.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <FormError :message="transactionForm.errors.category_id" />
                                </FormGroup>
                            </div>

                            <div class="flex items-center gap-3">
                                <Button type="submit" :disabled="transactionForm.processing">
                                    {{ $t('onboarding.actions.finish') }}
                                </Button>
                                <Link
                                    :href="dashboard().url"
                                    class="text-sm text-muted-foreground underline underline-offset-4 hover:text-foreground"
                                >
                                    {{ $t('onboarding.actions.skip_to_dashboard') }}
                                </Link>
                            </div>
                        </Form>
                    </template>
                </CardContent>
            </Card>

            <p class="text-center text-xs text-muted-foreground">
                {{ $t('onboarding.footer') }}
            </p>
        </main>
    </DefaultLayout>
</template>
