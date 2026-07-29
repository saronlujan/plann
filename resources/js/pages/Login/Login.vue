<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { register } from '../../routes';
import { store as loginStore } from '../../routes/login';
import { request as forgotPassword } from '../../routes/password';

const props = defineProps<{
    googleOAuthEnabled: boolean;
}>();

const canUseGoogleLogin = computed(() => props.googleOAuthEnabled);

/**
 * Login failures are announced, not annotated.
 *
 * "These credentials do not match our records" is about the pair, and the server
 * hangs it on the email field — printed under that input it reads as if only the
 * address were wrong.
 */
function showErrors(errors: Record<string, string>): void {
    const message = Object.values(errors)[0];

    if (message) {
        toast.error(message);
    }
}
</script>

<template>
    <Head :title="trans('auth.ui.login.title')" />

    <AuthLayout>
        <section class="w-full space-y-8 text-center">
            <div class="space-y-1 text-left">
                <h1 class="text-xl font-semibold">{{ $t('auth.ui.login.title') }}</h1>
                <p class="text-sm text-zinc-500">{{ $t('auth.ui.login.subtitle') }}</p>
            </div>

            <Form
                :action="loginStore.form().action"
                method="post"
                class="space-y-4 text-left"
                #default="{ processing }"
                @error="showErrors"
            >
                <label class="flex flex-col gap-2">
                    <span class="text-sm font-medium text-zinc-700">{{
                        $t('auth.ui.login.email_label')
                    }}</span>
                    <Input type="email" name="email" autocomplete="email" />
                </label>

                <div class="flex flex-col gap-2">
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
    </AuthLayout>
</template>
