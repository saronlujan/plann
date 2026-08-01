<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { Pencil } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Form, FormError, FormGroup, FormLabel } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import DefaultLayout from '@/layouts/DefaultLayout.vue';
import { password as passwordRoute, update as updateProfile } from '@/routes/profile';
import { update as updateAvatar } from '@/routes/profile/avatar';
import AvatarCropper from './components/AvatarCropper.vue';

const props = defineProps<{
    profile: { name: string; email: string; phone: string | null };
}>();

// The email is the verified login identity and is not editable, so it is shown
// read-only and deliberately kept out of the form payload.
const accountForm = useForm({
    name: props.profile.name,
    phone: props.profile.phone ?? '',
});

const page = usePage<{ auth: { user: { name: string; avatar_url: string | null } | null } }>();

const currentAvatarUrl = computed(() => page.props.auth.user?.avatar_url ?? undefined);

const initials = computed(() =>
    (page.props.auth.user?.name ?? '?')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join(''),
);

// The crop travels as plain numbers; the server does the cutting.
const avatarForm = useForm<{
    avatar: File | null;
    crop_x: number;
    crop_y: number;
    crop_size: number;
}>({
    avatar: null,
    crop_x: 0,
    crop_y: 0,
    crop_size: 0,
});

const avatarInputRef = ref<HTMLInputElement | null>(null);

function openFilePicker(): void {
    avatarInputRef.value?.click();
}

function onAvatarPicked(event: Event): void {
    avatarForm.clearErrors();
    avatarForm.avatar = (event.target as HTMLInputElement).files?.[0] ?? null;
}

function onCropChange(crop: { x: number; y: number; size: number }): void {
    avatarForm.crop_x = crop.x;
    avatarForm.crop_y = crop.y;
    avatarForm.crop_size = crop.size;
}

function cancelAvatar(): void {
    avatarForm.reset();
    avatarForm.clearErrors();

    // Cleared by hand: picking the same file twice fires no change event
    // otherwise, so the picker would look dead.
    if (avatarInputRef.value) {
        avatarInputRef.value.value = '';
    }
}

function submitAvatar(): void {
    avatarForm.post(updateAvatar().url, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            cancelAvatar();
            toast.success(trans('profile.avatar.saved'));
        },
    });
}

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
            <PageHeader :title="$t('profile.title')" :subtitle="$t('profile.subtitle')" />

            <Card>
                <div
                    class="grid grid-cols-12 gap-5 border-b border-zinc-100 pb-6 dark:border-zinc-800"
                >
                    <div class="col-span-12 flex flex-col lg:col-span-4">
                        <h2 class="font-medium">{{ $t('profile.avatar.title') }}</h2>
                        <span class="text-xs font-medium text-zinc-400 dark:text-zinc-500">
                            {{ $t('profile.avatar.description') }}
                        </span>
                    </div>
                    <div class="col-span-12 lg:col-span-8">
                        <div class="flex max-w-md flex-col gap-3">
                            <!--
                                The circle is the control: clicking it opens the
                                picker, so the file input has no reason to be on
                                screen at all.
                            -->
                            <button
                                v-if="!avatarForm.avatar"
                                type="button"
                                class="group relative size-20 shrink-0 rounded-full"
                                :aria-label="$t('profile.avatar.change')"
                                @click="openFilePicker"
                            >
                                <!-- Same zinc as the card dividers, a step thicker
                                     so the circle reads as a control. -->
                                <Avatar
                                    class="size-20 border-4 border-zinc-200 dark:border-zinc-700"
                                >
                                    <AvatarImage
                                        v-if="currentAvatarUrl"
                                        :src="currentAvatarUrl"
                                        alt=""
                                    />
                                    <AvatarFallback class="text-lg">{{ initials }}</AvatarFallback>
                                </Avatar>

                                <span
                                    class="absolute inset-0 flex items-center justify-center rounded-full bg-black/50 text-white opacity-0 transition-opacity group-hover:opacity-100 group-focus-visible:opacity-100"
                                >
                                    <Pencil class="size-5" />
                                </span>
                            </button>

                            <input
                                ref="avatarInputRef"
                                type="file"
                                class="hidden"
                                accept="image/jpeg,image/png,image/webp"
                                @change="onAvatarPicked"
                            />

                            <FormError :message="avatarForm.errors.avatar" />

                            <template v-if="avatarForm.avatar">
                                <AvatarCropper
                                    :file="avatarForm.avatar"
                                    :size="200"
                                    @change="onCropChange"
                                />

                                <div class="flex gap-2">
                                    <Button
                                        type="button"
                                        :disabled="avatarForm.processing"
                                        @click="submitAvatar"
                                    >
                                        {{ $t('common.actions.save') }}
                                    </Button>
                                    <Button type="button" variant="outline" @click="cancelAvatar">
                                        {{ $t('common.actions.cancel') }}
                                    </Button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

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
