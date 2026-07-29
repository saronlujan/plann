<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { InputOTP, InputOTPGroup, InputOTPSlot } from '@/components/ui/input-otp';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { update as resetPassword, verify as verifyPin } from '@/routes/password';

defineProps<{ verified: boolean }>();

const pinForm = useForm({ pin: '' });
const passwordForm = useForm({ password: '', password_confirmation: '' });

const pinDigits = computed<string[]>({
    get: () => pinForm.pin.split(''),
    set: (value) => (pinForm.pin = value.join('')),
});

function verify(): void {
    pinForm.post(verifyPin().url, { preserveScroll: true });
}

function reset(): void {
    passwordForm.post(resetPassword().url, {
        onError: () => passwordForm.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <Head :title="$t('auth.ui.reset.title')" />

    <AuthLayout>
        <section class="w-full space-y-6">
            <div class="space-y-1 text-left">
                <h1 class="text-xl font-semibold">{{ $t('auth.ui.reset.title') }}</h1>
                <p class="text-sm text-zinc-500">
                    {{
                        verified
                            ? $t('auth.ui.reset.set_new_password')
                            : $t('auth.ui.reset.subtitle')
                    }}
                </p>
            </div>

            <!-- Step 1: verify the PIN -->
            <form v-if="!verified" class="space-y-4 text-left" @submit.prevent="verify">
                <div class="flex flex-col items-center space-y-2">
                    <span class="text-sm font-medium text-zinc-700">
                        {{ $t('auth.ui.reset.pin_label') }}
                    </span>
                    <InputOTP v-model="pinDigits" otp>
                        <InputOTPGroup>
                            <InputOTPSlot
                                v-for="i in 6"
                                :key="i"
                                :index="i - 1"
                                inputmode="numeric"
                            />
                        </InputOTPGroup>
                    </InputOTP>
                    <span class="block text-xs text-zinc-500">
                        {{ $t('auth.ui.reset.pin_hint') }}
                    </span>
                    <span v-if="pinForm.errors.pin" class="block text-sm text-red-600">
                        {{ pinForm.errors.pin }}
                    </span>
                </div>

                <Button type="submit" :disabled="pinForm.processing" class="w-full">
                    {{ $t('auth.ui.reset.verify_submit') }}
                </Button>
            </form>

            <!-- Step 2: set the new password (unlocked after verification) -->
            <form v-else class="space-y-4 text-left" @submit.prevent="reset">
                <label class="block space-y-2">
                    <span class="text-sm font-medium text-zinc-700">
                        {{ $t('auth.ui.reset.password_label') }}
                    </span>
                    <Input
                        v-model="passwordForm.password"
                        type="password"
                        autocomplete="new-password"
                    />
                    <span v-if="passwordForm.errors.password" class="text-sm text-red-600">
                        {{ passwordForm.errors.password }}
                    </span>
                </label>

                <label class="block space-y-2">
                    <span class="text-sm font-medium text-zinc-700">
                        {{ $t('auth.ui.reset.password_confirmation_label') }}
                    </span>
                    <Input
                        v-model="passwordForm.password_confirmation"
                        type="password"
                        autocomplete="new-password"
                    />
                </label>

                <Button type="submit" :disabled="passwordForm.processing" class="w-full">
                    {{ $t('auth.ui.reset.submit') }}
                </Button>
            </form>

            <p class="text-center text-sm text-zinc-500">
                <Link :href="login().url" class="font-medium text-zinc-950 hover:underline">
                    {{ $t('auth.ui.forgot.back_to_login') }}
                </Link>
            </p>
        </section>
    </AuthLayout>
</template>
