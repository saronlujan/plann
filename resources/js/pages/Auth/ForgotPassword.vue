<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { email as sendPin } from '@/routes/password';

const form = useForm({ email: '' });

function submit(): void {
    form.post(sendPin().url, {
        onSuccess: () => toast.success(trans('auth.ui.forgot.sent')),
    });
}
</script>

<template>
    <Head :title="$t('auth.ui.forgot.title')" />

    <AuthLayout>
        <section class="w-full space-y-6">
            <div class="space-y-1 text-center">
                <h1 class="text-xl font-semibold">{{ $t('auth.ui.forgot.title') }}</h1>
                <p class="text-sm text-zinc-500">{{ $t('auth.ui.forgot.subtitle') }}</p>
            </div>

            <form class="space-y-4 text-left" @submit.prevent="submit">
                <label class="block space-y-2">
                    <span class="text-sm font-medium text-zinc-700">
                        {{ $t('auth.ui.forgot.email_label') }}
                    </span>
                    <Input
                        v-model="form.email"
                        type="email"
                        autocomplete="email"
                        :placeholder="$t('auth.ui.forgot.email_placeholder')"
                    />
                    <span v-if="form.errors.email" class="text-sm text-red-600">
                        {{ form.errors.email }}
                    </span>
                </label>

                <Button type="submit" :disabled="form.processing" class="w-full">
                    {{ $t('auth.ui.forgot.submit') }}
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
