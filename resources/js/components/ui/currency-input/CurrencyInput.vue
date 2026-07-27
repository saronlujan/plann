<script setup lang="ts">
import { type ComponentPublicInstance, computed, type HTMLAttributes, ref, watch } from 'vue';
import { Input } from '@/components/ui/input';
import { currencyFractionDigits, moneyLocale } from '@/lib/money';
import { cn } from '@/lib/utils';

/**
 * Money field that formats while you type.
 *
 * Digits fill in from the right — typing "1234" reads 12,34 — which is how every
 * banking app behaves and why the caret belongs at the end after each keystroke.
 * Currencies written without decimals (guaraní, peso) fill whole units instead,
 * so the same "1234" reads 1.234. The model stays a plain decimal string, so the
 * server keeps taking exactly what it already validates.
 */
defineOptions({ inheritAttrs: false });

const props = withDefaults(
    defineProps<{
        class?: HTMLAttributes['class'];
        modelValue?: string | number | null;
        /** Rendered inside the field, before the number. */
        symbol?: string;
        /** Decides the decimals: PYG and ARS take none, everything else cents. */
        code?: string;
        /** Opening balances may be negative; amounts and limits may not. */
        allowNegative?: boolean;
    }>(),
    {
        allowNegative: false,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

// 15 digits keeps the scaled integer inside Number.MAX_SAFE_INTEGER, so no amount
// silently loses precision on the way to the server.
const MAX_DIGITS = 15;

const fractionDigits = computed(() => (props.code ? currencyFractionDigits(props.code) : 2));

/** Multiplier between the typed integer and the decimal the server receives. */
const scale = computed(() => 10 ** fractionDigits.value);

const inputRef = ref<ComponentPublicInstance | null>(null);
const display = ref('');

/** What the model holds as a scaled integer; null when the field is empty. */
function scaledFromModel(value: string | number | null | undefined): number | null {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    const amount = typeof value === 'number' ? value : Number.parseFloat(value);

    return Number.isFinite(amount) ? Math.round(amount * scale.value) : null;
}

function formatScaled(scaled: number): string {
    const body = new Intl.NumberFormat(moneyLocale(), {
        minimumFractionDigits: fractionDigits.value,
        maximumFractionDigits: fractionDigits.value,
    }).format(Math.abs(scaled) / scale.value);

    return scaled < 0 ? `-${body}` : body;
}

/** Last value emitted, so an echo of our own update never rewrites the field. */
let lastEmitted = '';

function syncFromModel(value: string | number | null | undefined): void {
    const scaled = scaledFromModel(value);

    display.value = scaled === null ? '' : formatScaled(scaled);
}

syncFromModel(props.modelValue);
lastEmitted = props.modelValue == null ? '' : String(props.modelValue);

watch(
    () => props.modelValue,
    (value) => {
        // Only react to changes from outside — a reset, or an entry being edited.
        if ((value == null ? '' : String(value)) === lastEmitted) {
            return;
        }

        syncFromModel(value);
    },
);

watch(fractionDigits, () => syncFromModel(lastEmitted));

function handleInput(value: string | File | null): void {
    const typed = typeof value === 'string' ? value : '';
    const negative = props.allowNegative && typed.trimStart().startsWith('-');
    const digits = typed.replace(/\D/g, '').slice(0, MAX_DIGITS);

    if (digits === '') {
        // A lone minus is a half-typed number, not a value: keep it on screen.
        display.value = negative ? '-' : '';
        lastEmitted = '';
    } else {
        const scaled = Number.parseInt(digits, 10) * (negative ? -1 : 1);

        display.value = formatScaled(scaled);
        // Always two decimals on the wire: the column is decimal(x, 2) whether or
        // not the currency shows them.
        lastEmitted = (scaled / scale.value).toFixed(2);
    }

    // Rejected characters leave the DOM value and `display` out of step, and Vue
    // skips the patch when `display` did not change — so write it back by hand.
    const element = inputRef.value?.$el as HTMLInputElement | undefined;

    if (element && element.value !== display.value) {
        element.value = display.value;
    }

    emit('update:modelValue', lastEmitted);
}
</script>

<template>
    <div class="relative">
        <span
            v-if="props.symbol"
            class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-muted-foreground"
        >
            {{ props.symbol }}
        </span>

        <Input
            ref="inputRef"
            v-bind="$attrs"
            type="text"
            inputmode="decimal"
            autocomplete="off"
            :model-value="display"
            :class="cn(props.symbol ? 'pl-10' : '', props.class)"
            @update:model-value="handleInput"
        />
    </div>
</template>
