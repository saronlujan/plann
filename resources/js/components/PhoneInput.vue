<script setup lang="ts">
import { ChevronDownIcon } from '@lucide/vue';
import { onClickOutside } from '@vueuse/core';
import { computed, ref, watch } from 'vue';
import type { ComponentPublicInstance } from 'vue';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

type Country = {
    code: string;
    name: string;
    dialCode: string;
    flag: string;
    placeholder: string;
    /**
     * Digit slots as '#', shortest form first. Brazil keeps two: landlines have
     * eight digits after the area code, mobiles nine.
     */
    masks: string[];
};

const countries: Country[] = [
    {
        code: 'BR',
        name: 'Brasil',
        dialCode: '+55',
        flag: '🇧🇷',
        placeholder: '(11) 99999-9999',
        masks: ['(##) ####-####', '(##) #####-####'],
    },
    {
        code: 'AR',
        name: 'Argentina',
        dialCode: '+54',
        flag: '🇦🇷',
        placeholder: '11 2345-6789',
        masks: ['## ####-####'],
    },
    {
        code: 'PY',
        name: 'Paraguai',
        dialCode: '+595',
        flag: '🇵🇾',
        placeholder: '0981 123456',
        masks: ['#### ######'],
    },
    {
        code: 'UY',
        name: 'Uruguai',
        dialCode: '+598',
        flag: '🇺🇾',
        placeholder: '099 123 456',
        masks: ['### ### ###'],
    },
    {
        code: 'CL',
        name: 'Chile',
        dialCode: '+56',
        flag: '🇨🇱',
        placeholder: '9 1234 5678',
        masks: ['# #### ####'],
    },
    {
        code: 'US',
        name: 'Estados Unidos',
        dialCode: '+1',
        flag: '🇺🇸',
        placeholder: '(201) 555-0123',
        masks: ['(###) ###-####'],
    },
    {
        code: 'PT',
        name: 'Portugal',
        dialCode: '+351',
        flag: '🇵🇹',
        placeholder: '912 345 678',
        masks: ['### ### ###'],
    },
    {
        code: 'ES',
        name: 'Espanha',
        dialCode: '+34',
        flag: '🇪🇸',
        placeholder: '612 34 56 78',
        masks: ['### ## ## ##'],
    },
];

// The template reads the props directly, so the returned object is not bound.
withDefaults(defineProps<{ name?: string; placeholder?: string; disabled?: boolean }>(), {
    name: undefined,
    placeholder: undefined,
    disabled: false,
});

const model = defineModel<string>({ default: '' });

const open = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);
onClickOutside(dropdownRef, () => (open.value = false));

const selected = ref<Country>(countries[0]);

// Digits only: the mask is presentation, so the value never carries formatting
// the server would have to strip.
const national = ref('');

const inputRef = ref<ComponentPublicInstance | null>(null);

function digitsOf(value: string): string {
    return value.replace(/\D/g, '');
}

function slotsIn(mask: string): number {
    return mask.split('#').length - 1;
}

/** The shortest mask that still holds every digit typed so far. */
function maskFor(country: Country, digitCount: number): string {
    return (
        country.masks.find((mask) => slotsIn(mask) >= digitCount) ??
        country.masks[country.masks.length - 1]
    );
}

const maxDigits = computed(() => Math.max(...selected.value.masks.map((mask) => slotsIn(mask))));

const maxLength = computed(() => Math.max(...selected.value.masks.map((mask) => mask.length)));

/**
 * Lay the digits into the mask, stopping as soon as they run out — a trailing
 * separator would sit there looking like a typo until the next keystroke.
 */
const display = computed(() => {
    const digits = national.value;
    const mask = maskFor(selected.value, digits.length);

    let result = '';
    let index = 0;

    for (const character of mask) {
        if (index >= digits.length) {
            break;
        }

        if (character === '#') {
            result += digits[index];
            index += 1;

            continue;
        }

        result += character;
    }

    return result;
});

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
        national.value = digitsOf(trimmed.slice(match.dialCode.length));
    } else {
        national.value = digitsOf(trimmed);
    }
}

function compose(): string {
    const digits = national.value;

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
    // The new country may hold fewer digits than the old one.
    national.value = national.value.slice(0, maxDigits.value);
    update();
}

function onNational(value: string | number | File | null): void {
    const typed = typeof value === 'string' ? value : String(value ?? '');

    national.value = digitsOf(typed).slice(0, maxDigits.value);

    // A rejected character leaves the DOM out of step with `display`, and Vue
    // skips the patch when the masked result did not change — write it back.
    const element = inputRef.value?.$el as HTMLInputElement | undefined;

    if (element && element.value !== display.value) {
        element.value = display.value;
    }

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
            ref="inputRef"
            :model-value="display"
            :placeholder="placeholder ?? selected.placeholder"
            :maxlength="maxLength"
            :disabled="disabled"
            @update:model-value="onNational"
        />

        <input v-if="name" type="hidden" :name="name" :value="model" />
    </div>
</template>
