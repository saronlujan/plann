<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { CheckIcon } from '@lucide/vue';
import { trans } from 'laravel-vue-i18n';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { applyAppearance, paletteSwatch } from '@/composables/useAppearance';
import type { ColorValue, ThemeValue } from '@/composables/useAppearance';
import DefaultLayout from '@/layouts/DefaultLayout.vue';
import { playSound } from '@/lib/sound';
import type { SoundValue } from '@/lib/sound';
import { update as updatePreferences } from '@/routes/preferences';

type Option = { value: string; label: string };

const props = defineProps<{
    preferences: {
        locale: string;
        theme: ThemeValue;
        color: ColorValue;
        sound_enabled: boolean;
        sound_theme: SoundValue;
        notifications_enabled: boolean;
        notify_days_before: number;
    };
    localeOptions: Option[];
    themeOptions: Option[];
    colorOptions: Option[];
    soundOptions: Option[];
}>();

const locale = ref(props.preferences.locale);
const theme = ref<ThemeValue>(props.preferences.theme);
const color = ref<ColorValue>(props.preferences.color);
const soundEnabled = ref(props.preferences.sound_enabled);
const soundTheme = ref<SoundValue>(props.preferences.sound_theme);
const notificationsEnabled = ref(props.preferences.notifications_enabled);
const notifyDaysBefore = ref(String(props.preferences.notify_days_before));

const daysBeforeOptions: Option[] = [
    { value: '1', label: trans('preferences.days_before.n1') },
    { value: '3', label: trans('preferences.days_before.n3') },
    { value: '5', label: trans('preferences.days_before.n5') },
    { value: '7', label: trans('preferences.days_before.n7') },
    { value: '10', label: trans('preferences.days_before.n10') },
];

function persist(): void {
    applyAppearance(theme.value, color.value);

    router.patch(
        updatePreferences().url,
        {
            locale: locale.value,
            theme: theme.value,
            color: color.value,
            sound_enabled: soundEnabled.value,
            sound_theme: soundTheme.value,
            notifications_enabled: notificationsEnabled.value,
            notify_days_before: Number(notifyDaysBefore.value),
        },
        { preserveScroll: true, preserveState: true },
    );
}

function selectSound(): void {
    playSound(soundTheme.value);
    persist();
}

function selectTheme(value: string): void {
    theme.value = value as ThemeValue;
    persist();
}

function selectColor(value: string): void {
    color.value = value as ColorValue;
    persist();
}
</script>

<template>
    <Head :title="$t('preferences.title')" />

    <DefaultLayout>
        <main class="flex flex-col gap-5 p-3 md:p-5">
            <div class="flex flex-col">
                <h1 class="text-lg font-semibold md:text-xl">{{ $t('preferences.title') }}</h1>
                <span class="text-sm text-muted-foreground">
                    {{ $t('preferences.subtitle') }}
                </span>
            </div>
            <Card>
                <div
                    class="grid grid-cols-12 gap-5 border-b border-zinc-100 pb-5 dark:border-zinc-800"
                >
                    <div class="col-span-12 flex flex-col lg:col-span-4">
                        <h2 class="font-medium">{{ $t('preferences.language.title') }}</h2>
                        <span class="text-xs font-medium text-zinc-400 dark:text-zinc-500">{{
                            $t('preferences.language.description')
                        }}</span>
                    </div>
                    <div class="col-span-12 lg:col-span-8">
                        <Select v-model="locale" @update:model-value="persist">
                            <SelectTrigger class="mt-1 max-w-xs">
                                <SelectValue
                                    :placeholder="$t('preferences.language.placeholder')"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in localeOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div
                    class="grid grid-cols-12 gap-5 border-b border-zinc-100 pb-5 dark:border-zinc-800"
                >
                    <div class="col-span-12 flex flex-col lg:col-span-4">
                        <h2 class="font-medium">{{ $t('preferences.theme.title') }}</h2>
                        <span class="text-xs font-medium text-zinc-400 dark:text-zinc-500">{{
                            $t('preferences.theme.description')
                        }}</span>
                    </div>
                    <div class="col-span-12 lg:col-span-8">
                        <div class="mt-1 flex gap-2">
                            <Button
                                v-for="option in themeOptions"
                                :key="option.value"
                                type="button"
                                :variant="theme === option.value ? 'default' : 'outline'"
                                :aria-pressed="theme === option.value"
                                @click="selectTheme(option.value)"
                            >
                                {{ option.label }}
                            </Button>
                        </div>
                    </div>
                </div>

                <div
                    class="grid grid-cols-12 gap-5 border-b border-zinc-100 pb-5 dark:border-zinc-800"
                >
                    <div class="col-span-12 flex flex-col lg:col-span-4">
                        <h2 class="font-medium">{{ $t('preferences.color.title') }}</h2>
                        <span class="text-xs font-medium text-zinc-400 dark:text-zinc-500">{{
                            $t('preferences.color.description')
                        }}</span>
                    </div>
                    <div class="col-span-12 lg:col-span-8">
                        <div class="mt-1 flex flex-wrap gap-3">
                            <button
                                v-for="option in colorOptions"
                                :key="option.value"
                                type="button"
                                :aria-label="option.label"
                                :aria-pressed="color === option.value"
                                class="flex size-9 items-center justify-center rounded-full border transition"
                                :class="
                                    color === option.value
                                        ? 'ring-2 ring-ring ring-offset-2'
                                        : 'border-border'
                                "
                                :style="{
                                    backgroundColor: paletteSwatch(option.value as ColorValue),
                                }"
                                @click="selectColor(option.value)"
                            >
                                <CheckIcon
                                    v-if="color === option.value"
                                    class="size-4 text-white"
                                    aria-hidden="true"
                                />
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    class="grid grid-cols-12 gap-5"
                    :class="
                        soundEnabled ? 'border-b border-zinc-100 pb-5 dark:border-zinc-800' : ''
                    "
                >
                    <div class="col-span-12 flex flex-col lg:col-span-4">
                        <h2 class="font-medium">{{ $t('preferences.sound.title') }}</h2>
                        <span class="text-xs font-medium text-zinc-400 dark:text-zinc-500">{{
                            $t('preferences.sound.description')
                        }}</span>
                    </div>
                    <div class="col-span-12 lg:col-span-8">
                        <Switch
                            v-model="soundEnabled"
                            class="mt-1"
                            :aria-label="$t('preferences.sound.aria_label')"
                            @update:model-value="persist"
                        />
                    </div>
                </div>

                <div v-if="soundEnabled" class="grid grid-cols-12 gap-5">
                    <div class="col-span-12 flex flex-col lg:col-span-4">
                        <h2 class="font-medium">{{ $t('preferences.sound_type.title') }}</h2>
                        <span class="text-xs font-medium text-zinc-400 dark:text-zinc-500">{{
                            $t('preferences.sound_type.description')
                        }}</span>
                    </div>
                    <div class="col-span-12 lg:col-span-8">
                        <Select v-model="soundTheme" @update:model-value="selectSound">
                            <SelectTrigger class="mt-1 max-w-xs">
                                <SelectValue
                                    :placeholder="$t('preferences.sound_type.placeholder')"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in soundOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div
                    class="mt-5 grid grid-cols-12 gap-5 border-t border-zinc-100 pt-5 dark:border-zinc-800"
                    :class="
                        notificationsEnabled
                            ? 'border-b border-zinc-100 pb-5 dark:border-zinc-800'
                            : ''
                    "
                >
                    <div class="col-span-12 flex flex-col lg:col-span-4">
                        <h2 class="font-medium">{{ $t('preferences.notifications.title') }}</h2>
                        <span class="text-xs font-medium text-zinc-400 dark:text-zinc-500">{{
                            $t('preferences.notifications.description')
                        }}</span>
                    </div>
                    <div class="col-span-12 lg:col-span-8">
                        <Switch
                            v-model="notificationsEnabled"
                            class="mt-1"
                            :aria-label="$t('preferences.notifications.aria_label')"
                            @update:model-value="persist"
                        />
                    </div>
                </div>

                <template v-if="notificationsEnabled">
                    <div class="grid grid-cols-12 gap-5">
                        <div class="col-span-12 flex flex-col lg:col-span-4">
                            <h2 class="font-medium">{{ $t('preferences.reminder.title') }}</h2>
                            <span class="text-xs font-medium text-zinc-400 dark:text-zinc-500">{{
                                $t('preferences.reminder.description')
                            }}</span>
                        </div>
                        <div class="col-span-12 lg:col-span-8">
                            <Select v-model="notifyDaysBefore" @update:model-value="persist">
                                <SelectTrigger class="mt-1 max-w-xs">
                                    <SelectValue
                                        :placeholder="$t('preferences.reminder.placeholder')"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in daysBeforeOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </template>
            </Card>
        </main>
    </DefaultLayout>
</template>
