<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';
import PhoneInput from '@/components/PhoneInput.vue';
import { Button } from '@/components/ui/button';
import { FormError } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { formatMoney } from '@/lib/money';
import { cn } from '@/lib/utils';
import { login } from '../../routes';
import { store as registerStore } from '../../routes/register';

type CountryOption = { value: string; label: string; currency: string };
type PlanOption = {
    value: string;
    label: string;
    description: string;
    monthly_price_cents: number;
    annual_price_cents: number;
};

const props = defineProps<{
    googleOAuthEnabled: boolean;
    planOptions: PlanOption[];
    defaultCountry: string | null;
    countryOptions: CountryOption[];
    currencyOptions: { value: string; label: string }[];
}>();

// The server guesses from the browser's languages; the list order is only the
// last resort.
const initialCountry =
    props.countryOptions.find((option) => option.value === props.defaultCountry) ??
    props.countryOptions[0];

// Gates country-specific behaviour later on.
const country = ref(initialCountry?.value ?? '');

// The currency the workspace opens on. It follows the country by default, but
// stays editable: someone in Brazil may well want to track USD.
const currency = ref(initialCountry?.currency ?? props.currencyOptions[0]?.value ?? '');

watch(country, (code) => {
    const selected = props.countryOptions.find((option) => option.value === code);

    if (selected) {
        currency.value = selected.currency;
    }
});

// Basic first, as the plans arrive sorted: the cheaper tier is the safe default
// for someone who has not read the difference yet.
const plan = ref(props.planOptions[0]?.value ?? '');

// Plans are billed in BRL regardless of the workspace's own currency.
function planPrice(cents: number): string {
    return formatMoney(cents / 100, 'BRL');
}

// The yearly total of whatever is selected: the cards advertise a monthly
// figure, and that is not the number that gets charged.
const selectedAnnualPrice = computed(() => {
    const option = props.planOptions.find((candidate) => candidate.value === plan.value);

    return planPrice(option?.annual_price_cents ?? 0);
});

const canUseGoogleLogin = computed(() => props.googleOAuthEnabled);
</script>

<template>
    <Head :title="trans('auth.ui.register.title')" />

    <AuthLayout>
        <section class="w-full space-y-8 text-center">
            <div class="space-y-1 text-left">
                <h1 class="text-xl font-semibold">{{ $t('auth.ui.register.title') }}</h1>
                <p class="text-sm text-zinc-500">{{ $t('auth.ui.register.subtitle') }}</p>
            </div>

            <Form
                :action="registerStore.form().action"
                method="post"
                class="space-y-4 text-left"
                #default="{ errors, processing }"
            >
                <label class="flex flex-col gap-2">
                    <span class="text-sm font-medium text-zinc-700">{{
                        $t('auth.ui.register.name_label')
                    }}</span>
                    <Input
                        type="text"
                        name="name"
                        autocomplete="name"
                        :placeholder="$t('auth.ui.register.name_placeholder')"
                    />
                    <FormError :message="errors.name" />
                </label>

                <label class="flex flex-col gap-2">
                    <span class="text-sm font-medium text-zinc-700">{{
                        $t('auth.ui.register.email_label')
                    }}</span>
                    <Input type="email" name="email" autocomplete="email" />
                    <FormError :message="errors.email" />
                </label>

                <div class="grid grid-cols-2 gap-3">
                    <div class="flex flex-col gap-2">
                        <span class="text-sm font-medium text-zinc-700">{{
                            $t('auth.ui.register.country_label')
                        }}</span>
                        <Select v-model="country">
                            <SelectTrigger class="w-full">
                                <SelectValue
                                    :placeholder="$t('auth.ui.register.country_placeholder')"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in countryOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <input type="hidden" name="country_code" :value="country" />
                        <FormError :message="errors.country_code" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <span class="text-sm font-medium text-zinc-700">{{
                            $t('auth.ui.register.currency_label')
                        }}</span>
                        <Select v-model="currency">
                            <SelectTrigger class="w-full">
                                <SelectValue
                                    :placeholder="$t('auth.ui.register.currency_placeholder')"
                                />
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
                        <input type="hidden" name="currency_code" :value="currency" />
                        <FormError :message="errors.currency_code" />
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <span class="text-sm font-medium text-zinc-700">{{
                        $t('auth.ui.register.phone_label')
                    }}</span>
                    <PhoneInput name="phone" />
                    <FormError :message="errors.phone" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <label class="flex flex-col gap-2">
                        <span class="text-sm font-medium text-zinc-700">{{
                            $t('auth.ui.register.password_label')
                        }}</span>
                        <Input type="password" name="password" autocomplete="new-password" />
                    </label>

                    <label class="flex flex-col gap-2">
                        <span class="text-sm font-medium text-zinc-700">{{
                            $t('auth.ui.register.password_confirmation_label')
                        }}</span>
                        <Input
                            type="password"
                            name="password_confirmation"
                            autocomplete="new-password"
                        />
                    </label>

                    <!--
                        Spans both columns: the rule that fails is usually about the
                        pair (too short, not matching), not about one of the boxes.
                    -->
                    <FormError class="col-span-2" :message="errors.password" />
                </div>

                <div class="flex flex-col gap-2">
                    <p class="mb-2 text-center text-sm font-bold text-zinc-950">
                        {{ $t('auth.ui.register.plan_trial_headline') }}
                    </p>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <button
                            v-for="option in planOptions"
                            :key="option.value"
                            type="button"
                            :aria-pressed="plan === option.value"
                            :class="
                                cn(
                                    'flex h-full flex-col gap-1 rounded-lg border p-3 text-left transition',
                                    plan === option.value
                                        ? 'border-zinc-950 bg-zinc-50 ring-1 ring-zinc-950'
                                        : 'border-zinc-200 hover:border-zinc-400',
                                )
                            "
                            @click="plan = option.value"
                        >
                            <span class="flex items-baseline justify-between gap-2">
                                <span class="font-medium text-zinc-950">{{ option.label }}</span>
                                <span class="text-sm font-semibold whitespace-nowrap text-zinc-950">
                                    {{ planPrice(option.monthly_price_cents)
                                    }}<span class="font-normal text-zinc-500">{{
                                        $t('billing.plan.per_month')
                                    }}</span>
                                </span>
                            </span>
                            <span class="text-xs leading-5 text-zinc-500">
                                {{ option.description }}
                            </span>
                        </button>
                    </div>
                    <input type="hidden" name="plan_slug" :value="plan" />
                    <p class="text-center text-xs leading-relaxed font-medium text-zinc-950">
                        {{
                            $t('auth.ui.register.plan_annual_notice', {
                                value: selectedAnnualPrice,
                            })
                        }}
                    </p>
                    <FormError :message="errors.plan_slug" />
                </div>

                <p class="text-sm leading-6 text-zinc-500">
                    {{ $t('auth.ui.register.terms_prefix') }}
                    <a href="#" class="font-medium text-zinc-950 hover:underline">
                        {{ $t('auth.ui.register.terms_link') }} </a
                    >.
                </p>

                <Button type="submit" :disabled="processing" class="w-full">
                    {{ $t('auth.ui.register.submit') }}
                </Button>

                <div class="flex items-center gap-4 pt-2 text-sm text-zinc-400">
                    <span class="h-px flex-1 bg-zinc-200"></span>
                    <span>{{ $t('auth.ui.register.or_divider') }}</span>
                    <span class="h-px flex-1 bg-zinc-200"></span>
                </div>

                <Button
                    v-if="canUseGoogleLogin"
                    as-child
                    type="button"
                    variant="outline"
                    class="w-full gap-2"
                >
                    <a href="/auth/google/redirect">{{ $t('auth.ui.social.google') }}</a>
                </Button>

                <p v-else class="text-center text-sm text-zinc-500">
                    {{ $t('auth.ui.social.google_not_configured') }}
                </p>
            </Form>

            <p class="text-sm text-zinc-500">
                {{ $t('auth.ui.register.have_account') }}
                <Link :href="login().url" class="font-medium text-zinc-950 hover:underline">
                    {{ $t('auth.ui.register.sign_in') }}
                </Link>
            </p>
        </section>
    </AuthLayout>
</template>
