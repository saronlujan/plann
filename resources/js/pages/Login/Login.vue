<script setup lang="ts">
import { computed } from 'vue';
import { trans } from 'laravel-vue-i18n';
import { Form, Head, Link } from '@inertiajs/vue3';
import { Checkbox } from '@/components/ui/checkbox';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { register } from '../../routes';
import { store as loginStore } from '../../routes/login';
import { request as forgotPassword } from '../../routes/password';

const props = defineProps<{
    googleOAuthEnabled: boolean;
}>();

const canUseGoogleLogin = computed(() => props.googleOAuthEnabled);
</script>

<template>
    <Head :title="trans('auth.ui.login.title')" />

    <main class="min-h-screen bg-white px-4 py-12 text-zinc-950">
        <div
            class="mx-auto flex min-h-[calc(100vh-6rem)] w-full max-w-md items-center justify-center"
        >
            <section class="w-full space-y-8 text-center">
                <Form
                    :action="loginStore().action"
                    method="post"
                    class="space-y-4 text-left"
                    #default="{ errors, processing }"
                >
                    <label class="block space-y-2">
                        <span class="text-sm font-medium text-zinc-700">{{
                            $t('auth.ui.login.email_label')
                        }}</span>
                        <Input
                            type="email"
                            name="email"
                            autocomplete="email"
                            :placeholder="$t('auth.ui.login.email_placeholder')"
                        />
                        <span v-if="errors.email" class="text-sm text-red-600">{{
                            errors.email
                        }}</span>
                    </label>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between gap-4">
                            <label class="text-sm font-medium text-zinc-700">{{
                                $t('auth.ui.login.password_label')
                            }}</label>
                            <Link
                                :href="forgotPassword().url"
                                class="text-xs font-medium text-zinc-500 hover:underline"
                            >
                                {{ $t('auth.ui.login.forgot_password') }}
                            </Link>
                        </div>
                        <Input type="password" name="password" autocomplete="current-password" />
                        <span v-if="errors.password" class="text-sm text-red-600">{{
                            errors.password
                        }}</span>
                    </div>

                    <label class="flex items-center gap-3 text-sm text-zinc-600">
                        <Checkbox id="remember" name="remember" />
                        <span>{{ $t('auth.ui.login.remember') }}</span>
                    </label>

                    <Button type="submit" :disabled="processing" class="w-full">
                        {{ $t('auth.ui.login.submit') }}
                    </Button>

                    <div class="flex items-center gap-4 pt-2 text-sm text-zinc-400">
                        <span class="h-px flex-1 bg-zinc-200"></span>
                        <span>{{ $t('auth.ui.login.or_divider') }}</span>
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
                    {{ $t('auth.ui.login.no_account') }}
                    <Link :href="register().url" class="font-medium text-zinc-950 hover:underline">
                        {{ $t('auth.ui.login.create_account') }}
                    </Link>
                </p>
            </section>
        </div>
    </main>
</template>
