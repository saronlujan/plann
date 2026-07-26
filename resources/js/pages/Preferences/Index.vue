<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { CheckIcon } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardTitle } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    applyAppearance,
    paletteSwatch,
    type ColorValue,
    type ThemeValue,
} from '@/composables/useAppearance';
import DefaultLayout from '@/layouts/DefaultLayout.vue';
import { update as updatePreferences } from '@/routes/preferences';

type Option = { value: string; label: string };

const props = defineProps<{
    preferences: { locale: string; theme: ThemeValue; color: ColorValue };
    localeOptions: Option[];
    themeOptions: Option[];
    colorOptions: Option[];
}>();

const locale = ref(props.preferences.locale);
const theme = ref<ThemeValue>(props.preferences.theme);
const color = ref<ColorValue>(props.preferences.color);

function persist(): void {
    applyAppearance(theme.value, color.value);

    router.patch(
        updatePreferences().url,
        { locale: locale.value, theme: theme.value, color: color.value },
        { preserveScroll: true, preserveState: true },
    );
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
    <Head title="Preferences" />

    <DefaultLayout>
        <main class="flex flex-col gap-5 p-3 md:p-5">
            <div class="flex flex-col">
                <h1 class="text-lg font-semibold md:text-xl">Preferences</h1>
                <span class="text-sm text-muted-foreground">
                    Language, theme, and accent color of the interface.
                </span>
            </div>
            <Card>
                <div
                    class="grid grid-cols-12 gap-5 border-b border-zinc-100 pb-5 dark:border-zinc-800"
                >
                    <div class="col-span-12 flex flex-col lg:col-span-4">
                        <h2 class="font-medium">Language</h2>
                        <span class="text-xs font-medium text-zinc-400 dark:text-zinc-500"
                            >Select the language of the interface.</span
                        >
                    </div>
                    <div class="col-span-12 lg:col-span-8">
                        <Select v-model="locale" @update:model-value="persist">
                            <SelectTrigger class="mt-1 max-w-xs">
                                <SelectValue placeholder="Selecione o idioma" />
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
                        <h2 class="font-medium">Theme</h2>
                        <span class="text-xs font-medium text-zinc-400 dark:text-zinc-500"
                            >Select the theme of the interface.</span
                        >
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
                        <h2 class="font-medium">Color</h2>
                        <span class="text-xs font-medium text-zinc-400 dark:text-zinc-500"
                            >Select the primary color applied to interface details.</span
                        >
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
            </Card>
        </main>
    </DefaultLayout>
</template>
