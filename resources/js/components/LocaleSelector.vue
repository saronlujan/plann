<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { usePage, router } from '@inertiajs/vue3';
import { loadLanguageAsync } from 'laravel-vue-i18n';
import { ChevronDown } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

type Locale = 'pt' | 'es' | 'en';

type LocaleOption = {
    code: Locale;
    label: string;
};

const page = usePage<{ locale?: Locale }>();
const selectedLocale = ref<Locale>(page.props.locale ?? 'en');

const locales: LocaleOption[] = [
    { code: 'pt', label: 'PT' },
    { code: 'es', label: 'ES' },
    { code: 'en', label: 'EN' },
];

const selectedLabel = computed(() => {
    return locales.find((locale) => locale.code === selectedLocale.value)?.label ?? 'EN';
});

watch(
    () => page.props.locale,
    (locale) => {
        selectedLocale.value = locale ?? 'en';
    },
);

function selectLocale(locale: Locale): void {
    if (locale === selectedLocale.value) {
        return;
    }

    router.patch(
        '/preferences/language',
        { locale },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedLocale.value = locale;
                void loadLanguageAsync(locale);
            },
        },
    );
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="sm" class="h-8 gap-1 text-sm font-medium">
                {{ selectedLabel }}
                <ChevronDown class="h-4 w-4 opacity-50" />
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" class="w-32">
            <DropdownMenuItem
                v-for="locale in locales"
                :key="locale.code"
                class="cursor-pointer"
                :class="{ 'bg-zinc-100': locale.code === selectedLocale }"
                @click="selectLocale(locale.code)"
            >
                {{ locale.label }}
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
