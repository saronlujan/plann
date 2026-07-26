<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { usePage, router } from '@inertiajs/vue3';
import { ChevronDownIcon, GlobeIcon } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { update as updateLocale } from '@/routes/locale';
import { update as updatePreferences } from '@/routes/preferences';

type Locale = 'pt' | 'es' | 'en';

type LocaleOption = {
    code: Locale;
    label: string;
};

const page = usePage<{ locale?: Locale; auth?: { user: unknown | null } }>();
const selectedLocale = ref<Locale>(page.props.locale ?? 'en');
const isAuthenticated = computed(() => page.props.auth?.user != null);

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

    selectedLocale.value = locale;

    // Persist; the server reload refreshes the shared translations and applies
    // the new language. Authenticated users save it to their profile; guests
    // (login/register/reset) store it in the session.
    if (isAuthenticated.value) {
        router.patch(updatePreferences().url, { locale }, { preserveScroll: true });
    } else {
        router.post(updateLocale().url, { locale }, { preserveScroll: true });
    }
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="sm" class="h-8 gap-1.5 text-sm font-medium">
                <GlobeIcon class="h-4 w-4 opacity-70" />
                {{ selectedLabel }}
                <ChevronDownIcon class="h-4 w-4 opacity-50" />
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" class="w-32">
            <DropdownMenuItem
                v-for="locale in locales"
                :key="locale.code"
                class="cursor-pointer"
                :class="{ 'bg-accent': locale.code === selectedLocale }"
                @click="selectLocale(locale.code)"
            >
                {{ locale.label }}
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
