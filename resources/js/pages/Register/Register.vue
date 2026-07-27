<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';
import PhoneInput from '@/components/PhoneInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { login } from '../../routes';
import { store as registerStore } from '../../routes/register';

type CountryOption = { value: string; label: string; currency: string };

const props = defineProps<{
    googleOAuthEnabled: boolean;
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

const canUseGoogleLogin = computed(() => props.googleOAuthEnabled);
</script>

<template>
    <Head :title="trans('auth.ui.register.title')" />

    <AuthLayout>
        <section class="w-full space-y-8 text-center">
            <Form
                :action="registerStore.form().action"
                method="post"
                class="space-y-4 text-left"
                #default="{ errors, processing }"
            >
                <label class="block space-y-2">
                    <span class="text-sm font-medium text-zinc-700">{{
                        $t('auth.ui.register.name_label')
                    }}</span>
                    <Input
                        type="text"
                        name="name"
                        autocomplete="name"
                        :placeholder="$t('auth.ui.register.name_placeholder')"
                    />
                    <span v-if="errors.name" class="text-sm text-red-600">{{ errors.name }}</span>
                </label>

                <label class="block space-y-2">
                    <span class="text-sm font-medium text-zinc-700">{{
                        $t('auth.ui.register.email_label')
                    }}</span>
                    <Input
                        type="email"
                        name="email"
                        autocomplete="email"
                        :placeholder="$t('auth.ui.register.email_placeholder')"
                    />
                    <span v-if="errors.email" class="text-sm text-red-600">{{ errors.email }}</span>
                </label>

                <div class="grid grid-cols-2 gap-3">
                    <div class="block space-y-2">
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
                        <span v-if="errors.country_code" class="text-sm text-red-600">{{
                            errors.country_code
                        }}</span>
                    </div>

                    <div class="block space-y-2">
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
                        <span v-if="errors.currency_code" class="text-sm text-red-600">{{
                            errors.currency_code
                        }}</span>
                    </div>
                </div>

                <div class="block space-y-2">
                    <span class="text-sm font-medium text-zinc-700">{{
                        $t('auth.ui.register.phone_label')
                    }}</span>
                    <PhoneInput name="phone" />
                    <span v-if="errors.phone" class="text-sm text-red-600">{{ errors.phone }}</span>
                </div>

                <label class="block space-y-2">
                    <span class="text-sm font-medium text-zinc-700">{{
                        $t('auth.ui.register.password_label')
                    }}</span>
                    <Input type="password" name="password" autocomplete="new-password" />
                    <span v-if="errors.password" class="text-sm text-red-600">{{
                        errors.password
                    }}</span>
                </label>

                <label class="block space-y-2">
                    <span class="text-sm font-medium text-zinc-700">{{
                        $t('auth.ui.register.password_confirmation_label')
                    }}</span>
                    <Input
                        type="password"
                        name="password_confirmation"
                        autocomplete="new-password"
                    />
                </label>

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
