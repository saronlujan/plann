<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Form, FormError, FormGroup, FormLabel } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import DefaultLayout from '@/layouts/DefaultLayout.vue';
import { password as passwordRoute, update as updateProfile } from '@/routes/profile';

const props = defineProps<{
    profile: { name: string; email: string; phone: string | null };
}>();

// The email is the verified login identity and is not editable, so it is shown
// read-only and deliberately kept out of the form payload.
const accountForm = useForm({
    name: props.profile.name,
    phone: props.profile.phone ?? '',
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function submitAccount(): void {
    accountForm.patch(updateProfile().url, {
        preserveScroll: true,
        onSuccess: () => toast.success(trans('profile.account.saved')),
    });
}

function submitPassword(): void {
    passwordForm.put(passwordRoute().url, {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
            toast.success(trans('profile.password.updated'));
        },
    });
}
</script>

<template>
    <Head :title="$t('profile.title')" />

    <DefaultLayout>
        <main class="flex flex-col gap-5 p-3 md:p-5">
            <div class="flex flex-col">
                <h1 class="text-lg font-semibold md:text-xl">{{ $t('profile.title') }}</h1>
                <span class="text-sm text-muted-foreground">{{ $t('profile.subtitle') }}</span>
            </div>

            <Card>
                <div
                    class="grid grid-cols-12 gap-5 border-b border-zinc-100 pb-6 dark:border-zinc-800"
                >
                    <div class="col-span-12 flex flex-col lg:col-span-4">
                        <h2 class="font-medium">{{ $t('profile.account.title') }}</h2>
                        <span class="text-xs font-medium text-zinc-400 dark:text-zinc-500">
                            {{ $t('profile.account.description') }}
                        </span>
                    </div>
                    <div class="col-span-12 lg:col-span-8">
                        <Form class="max-w-md gap-4" @submit.prevent="submitAccount">
                            <FormGroup>
                                <FormLabel for="p-name">{{ $t('profile.account.name') }}</FormLabel>
                                <Input id="p-name" v-model="accountForm.name" />
                                <FormError :message="accountForm.errors.name" />
                            </FormGroup>
                            <FormGroup>
                                <FormLabel for="p-email">{{
                                    $t('profile.account.email')
                                }}</FormLabel>
                                <Input
                                    id="p-email"
                                    :model-value="props.profile.email"
                                    type="email"
                                    readonly
                                    disabled
                                />
                                <span class="text-xs text-muted-foreground">
                                    {{ $t('profile.account.email_locked') }}
                                </span>
                            </FormGroup>
                            <FormGroup>
                                <FormLabel for="p-phone">{{
                                    $t('profile.account.phone')
                                }}</FormLabel>
                                <Input id="p-phone" v-model="accountForm.phone" />
                                <FormError :message="accountForm.errors.phone" />
                            </FormGroup>
                            <div>
                                <Button type="submit" :disabled="accountForm.processing">
                                    {{ $t('common.actions.save') }}
                                </Button>
                            </div>
                        </Form>
                    </div>
                </div>

                <div class="grid grid-cols-12 gap-5 pt-6">
                    <div class="col-span-12 flex flex-col lg:col-span-4">
                        <h2 class="font-medium">{{ $t('profile.password.title') }}</h2>
                        <span class="text-xs font-medium text-zinc-400 dark:text-zinc-500">
                            {{ $t('profile.password.description') }}
                        </span>
                    </div>
                    <div class="col-span-12 lg:col-span-8">
                        <Form class="max-w-md gap-4" @submit.prevent="submitPassword">
                            <FormGroup>
                                <FormLabel for="p-current">
                                    {{ $t('profile.password.current') }}
                                </FormLabel>
                                <Input
                                    id="p-current"
                                    v-model="passwordForm.current_password"
                                    type="password"
                                    autocomplete="current-password"
                                />
                                <FormError :message="passwordForm.errors.current_password" />
                            </FormGroup>
                            <FormGroup>
                                <FormLabel for="p-new">{{ $t('profile.password.new') }}</FormLabel>
                                <Input
                                    id="p-new"
                                    v-model="passwordForm.password"
                                    type="password"
                                    autocomplete="new-password"
                                />
                                <FormError :message="passwordForm.errors.password" />
                            </FormGroup>
                            <FormGroup>
                                <FormLabel for="p-confirm">
                                    {{ $t('profile.password.confirm') }}
                                </FormLabel>
                                <Input
                                    id="p-confirm"
                                    v-model="passwordForm.password_confirmation"
                                    type="password"
                                    autocomplete="new-password"
                                />
                            </FormGroup>
                            <div>
                                <Button type="submit" :disabled="passwordForm.processing">
                                    {{ $t('profile.password.submit') }}
                                </Button>
                            </div>
                        </Form>
                    </div>
                </div>
            </Card>
        </main>
    </DefaultLayout>
</template>
