<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { InputOTP, InputOTPGroup, InputOTPSlot } from '@/components/ui/input-otp';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { logout } from '@/routes';
import { resend, verify } from '@/routes/verification';

defineProps<{ email: string | null }>();

const pinForm = useForm({ pin: '' });

const pinDigits = computed<string[]>({
    get: () => pinForm.pin.split(''),
    set: (value) => (pinForm.pin = value.join('')),
});

function submit(): void {
    pinForm.post(verify().url, {
        onError: () => pinForm.reset('pin'),
    });
}

function resendPin(): void {
    router.post(resend().url, {}, { preserveScroll: true });
}
</script>

<template>
    <Head :title="$t('auth.ui.verify.title')" />

    <AuthLayout>
        <section class="w-full space-y-6">
            <div class="space-y-1 text-left">
                <h1 class="text-xl font-semibold">{{ $t('auth.ui.verify.title') }}</h1>
                <p class="text-sm text-zinc-500">
                    {{ $t('auth.ui.verify.subtitle', { email: email ?? '' }) }}
                </p>
            </div>

            <form class="space-y-4 text-left" @submit.prevent="submit">
                <div class="flex flex-col items-center space-y-2">
                    <span class="text-sm font-medium text-zinc-700">
                        {{ $t('auth.ui.verify.pin_label') }}
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
                        {{ $t('auth.ui.verify.pin_hint') }}
                    </span>
                    <span v-if="pinForm.errors.pin" class="block text-sm text-red-600">
                        {{ pinForm.errors.pin }}
                    </span>
                </div>

                <Button type="submit" :disabled="pinForm.processing" class="w-full">
                    {{ $t('auth.ui.verify.submit') }}
                </Button>
            </form>

            <div class="space-y-2 text-center text-sm text-zinc-500">
                <button
                    type="button"
                    class="font-medium text-zinc-950 hover:underline"
                    @click="resendPin"
                >
                    {{ $t('auth.ui.verify.resend') }}
                </button>
                <p>
                    <Link
                        :href="logout().url"
                        method="post"
                        as="button"
                        class="font-medium text-zinc-950 hover:underline"
                    >
                        {{ $t('auth.ui.verify.logout') }}
                    </Link>
                </p>
            </div>
        </section>
    </AuthLayout>
</template>
