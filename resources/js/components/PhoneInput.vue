<script setup lang="ts">
import { ChevronDownIcon } from '@lucide/vue';
import { onClickOutside } from '@vueuse/core';
import { ref, watch } from 'vue';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

type Country = {
    code: string;
    name: string;
    dialCode: string;
    flag: string;
    placeholder: string;
    maxLength: number;
};

const countries: Country[] = [
    {
        code: 'BR',
        name: 'Brasil',
        dialCode: '+55',
        flag: '🇧🇷',
        placeholder: '(11) 99999-9999',
        maxLength: 16,
    },
    {
        code: 'AR',
        name: 'Argentina',
        dialCode: '+54',
        flag: '🇦🇷',
        placeholder: '11 2345-6789',
        maxLength: 15,
    },
    {
        code: 'PY',
        name: 'Paraguai',
        dialCode: '+595',
        flag: '🇵🇾',
        placeholder: '0981 123456',
        maxLength: 15,
    },
    {
        code: 'UY',
        name: 'Uruguai',
        dialCode: '+598',
        flag: '🇺🇾',
        placeholder: '099 123 456',
        maxLength: 15,
    },
    {
        code: 'CL',
        name: 'Chile',
        dialCode: '+56',
        flag: '🇨🇱',
        placeholder: '9 1234 5678',
        maxLength: 15,
    },
    {
        code: 'US',
        name: 'Estados Unidos',
        dialCode: '+1',
        flag: '🇺🇸',
        placeholder: '(201) 555-0123',
        maxLength: 16,
    },
    {
        code: 'PT',
        name: 'Portugal',
        dialCode: '+351',
        flag: '🇵🇹',
        placeholder: '912 345 678',
        maxLength: 15,
    },
    {
        code: 'ES',
        name: 'Espanha',
        dialCode: '+34',
        flag: '🇪🇸',
        placeholder: '612 34 56 78',
        maxLength: 15,
    },
];

const props = withDefaults(
    defineProps<{ name?: string; placeholder?: string; disabled?: boolean }>(),
    { name: undefined, placeholder: undefined, disabled: false },
);

const model = defineModel<string>({ default: '' });

const open = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);
onClickOutside(dropdownRef, () => (open.value = false));

const selected = ref<Country>(countries[0]);
const national = ref('');

/**
 * Split an incoming value like "+55 11999999999" into a country + national part.
 */
function parseModel(value: string): void {
    const trimmed = (value ?? '').trim();

    const match = [...countries]
        .sort((a, b) => b.dialCode.length - a.dialCode.length)
        .find((country) => trimmed.startsWith(country.dialCode));

    if (match) {
        selected.value = match;
        national.value = trimmed.slice(match.dialCode.length).trim();
    } else {
        national.value = trimmed;
    }
}

function compose(): string {
    const digits = national.value.trim();

    return digits === '' ? '' : `${selected.value.dialCode} ${digits}`;
}

function update(): void {
    model.value = compose();
}

// Keep internal state in sync when the bound value changes externally (edit mode).
watch(
    model,
    (value) => {
        if (value !== compose()) {
            parseModel(value ?? '');
        }
    },
    { immediate: true },
);

function selectCountry(country: Country): void {
    selected.value = country;
    open.value = false;
    update();
}

function onNational(value: string | number | File | null): void {
    national.value = typeof value === 'string' ? value : String(value ?? '');
    update();
}
</script>

<template>
    <div class="flex w-full gap-2">
        <div ref="dropdownRef" class="relative shrink-0">
            <button
                type="button"
                :disabled="disabled"
                :aria-expanded="open"
                aria-haspopup="listbox"
                :aria-label="$t('contacts.phone.select_country')"
                class="flex h-9 items-center gap-1.5 rounded-md border border-input bg-transparent px-2.5 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30"
                @click="open = !open"
            >
                <span class="text-base leading-none">{{ selected.flag }}</span>
                <span class="font-medium text-muted-foreground">{{ selected.dialCode }}</span>
                <ChevronDownIcon
                    :class="cn('size-3.5 opacity-50 transition-transform', open && 'rotate-180')"
                    aria-hidden="true"
                />
            </button>

            <ul
                v-show="open"
                role="listbox"
                class="absolute left-0 z-50 mt-1 max-h-64 w-56 overflow-y-auto rounded-md border bg-popover py-1 text-popover-foreground shadow-md"
            >
                <li
                    v-for="country in countries"
                    :key="country.code"
                    role="option"
                    :aria-selected="selected.code === country.code"
                    :class="
                        cn(
                            'flex cursor-pointer items-center gap-2.5 px-3 py-2 text-sm transition-colors hover:bg-accent hover:text-accent-foreground',
                            selected.code === country.code && 'bg-accent/60',
                        )
                    "
                    @click="selectCountry(country)"
                >
                    <span class="text-base leading-none">{{ country.flag }}</span>
                    <span>{{ country.name }}</span>
                    <span class="ml-auto font-mono text-xs text-muted-foreground">{{
                        country.dialCode
                    }}</span>
                </li>
            </ul>
        </div>

        <Input
            type="tel"
            inputmode="numeric"
            autocomplete="tel"
            class="flex-1"
            :model-value="national"
            :placeholder="placeholder ?? selected.placeholder"
            :maxlength="selected.maxLength"
            :disabled="disabled"
            @update:model-value="onNational"
        />

        <input v-if="name" type="hidden" :name="name" :value="model" />
    </div>
</template>
