<script setup lang="ts">
import { computed } from 'vue';
import { Form, Head, Link } from '@inertiajs/vue3';
import { Checkbox } from '@/components/ui/checkbox';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { register } from '../../routes';
import { store as loginStore } from '../../routes/login';

const props = defineProps<{
    googleOAuthEnabled: boolean;
}>();

const canUseGoogleLogin = computed(() => props.googleOAuthEnabled);
</script>

<template>
    <Head title="Login" />

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
                        <span class="text-sm font-medium text-zinc-700">Email:</span>
                        <Input
                            type="email"
                            name="email"
                            autocomplete="email"
                            placeholder="johndoe@email.com"
                        />
                        <span v-if="errors.email" class="text-sm text-red-600">{{
                            errors.email
                        }}</span>
                    </label>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between gap-4">
                            <label class="text-sm font-medium text-zinc-700">Password</label>
                            <a href="#" class="text-xs font-medium text-zinc-500 hover:underline">
                                Forgot Password?
                            </a>
                        </div>
                        <Input type="password" name="password" autocomplete="current-password" />
                        <span v-if="errors.password" class="text-sm text-red-600">{{
                            errors.password
                        }}</span>
                    </div>

                    <label class="flex items-center gap-3 text-sm text-zinc-600">
                        <Checkbox id="remember" name="remember" />
                        <span>Keep me signed in</span>
                    </label>

                    <Button type="submit" :disabled="processing" class="w-full"> Login </Button>

                    <div class="flex items-center gap-4 pt-2 text-sm text-zinc-400">
                        <span class="h-px flex-1 bg-zinc-200"></span>
                        <span>or sign in with</span>
                        <span class="h-px flex-1 bg-zinc-200"></span>
                    </div>

                    <Button
                        v-if="canUseGoogleLogin"
                        as-child
                        type="button"
                        variant="outline"
                        class="w-full gap-2"
                    >
                        <a href="/auth/google/redirect">Continue with Google</a>
                    </Button>

                    <p v-else class="text-center text-sm text-zinc-500">
                        Google login is not configured yet.
                    </p>
                </Form>

                <p class="text-sm text-zinc-500">
                    Don't have an account?
                    <Link :href="register().url" class="font-medium text-zinc-950 hover:underline">
                        Create an account
                    </Link>
                </p>
            </section>
        </div>
    </main>
</template>
