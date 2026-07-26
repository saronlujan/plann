<script setup lang="ts">
import { computed } from 'vue';
import { trans } from 'laravel-vue-i18n';
import { Form, Head, Link } from '@inertiajs/vue3';
import PhoneInput from '@/components/PhoneInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { login } from '../../routes';
import { store as registerStore } from '../../routes/register';

const props = defineProps<{
    googleOAuthEnabled: boolean;
}>();

const canUseGoogleLogin = computed(() => props.googleOAuthEnabled);
</script>

<template>
    <Head :title="trans('auth.ui.register.title')" />

    <main class="min-h-screen bg-white px-4 py-12 text-zinc-950">
        <div
            class="mx-auto flex min-h-[calc(100vh-6rem)] w-full max-w-md items-center justify-center"
        >
            <section class="w-full space-y-8 text-center">
                <Form
                    :action="registerStore().action"
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
                        <span v-if="errors.name" class="text-sm text-red-600">{{
                            errors.name
                        }}</span>
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
                        <span v-if="errors.email" class="text-sm text-red-600">{{
                            errors.email
                        }}</span>
                    </label>

                    <div class="block space-y-2">
                        <span class="text-sm font-medium text-zinc-700">{{
                            $t('auth.ui.register.phone_label')
                        }}</span>
                        <PhoneInput name="phone" />
                        <span v-if="errors.phone" class="text-sm text-red-600">{{
                            errors.phone
                        }}</span>
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
        </div>
    </main>
</template>
