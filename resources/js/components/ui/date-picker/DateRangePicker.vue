<script setup lang="ts">
import { CalendarIcon } from '@lucide/vue';
import { DateFormatter, getLocalTimeZone, parseDate, today } from '@internationalized/date';
// From reka-ui, not from @internationalized/date: the calendar speaks its own
// re-export of these types, and the two are not interchangeable to TypeScript.
import type { DateRange, DateValue } from 'reka-ui';
import { getActiveLanguage, trans } from 'laravel-vue-i18n';
import { computed, shallowRef, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { RangeCalendar } from '@/components/ui/range-calendar';
import { cn } from '@/lib/utils';

/**
 * One control for a stretch of days, rather than two fields that each know half
 * of it. Nothing is emitted until both ends are picked: a lone date says nothing
 * about a range, and half of one would silently widen whatever it filters.
 */
const props = withDefaults(
    defineProps<{
        from?: string | null;
        to?: string | null;
        label?: string;
        placeholder?: string;
    }>(),
    {
        from: null,
        to: null,
        label: '',
        placeholder: '',
    },
);

const emit = defineEmits<{
    /** Both ends at once, or two empty strings when the range is cleared. */
    'update:range': [value: { from: string; to: string }];
}>();

const localeTag: Record<string, string> = { pt: 'pt-BR', en: 'en-US', es: 'es-ES' };

const formatter = new DateFormatter(localeTag[getActiveLanguage()] ?? 'pt-BR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
});

function toDateValue(value: string | null): DateValue | undefined {
    if (!value) {
        return undefined;
    }

    try {
        return parseDate(value);
    } catch {
        return undefined;
    }
}

// shallowRef, not ref: a deep ref unwraps the calendar's date union into a
// plain structural type and the component stops accepting its own value back.
const range = shallowRef<DateRange>({
    start: toDateValue(props.from),
    end: toDateValue(props.to),
});

// Follows the server: clearing the range elsewhere has to empty the control too.
watch(
    () => [props.from, props.to] as const,
    ([from, to]) => {
        range.value = { start: toDateValue(from), end: toDateValue(to) };
    },
);

function format(value: DateValue | undefined): string {
    return value ? formatter.format(value.toDate(getLocalTimeZone())) : '';
}

const buttonLabel = computed(() => {
    const { start, end } = range.value;

    if (start && end) {
        return `${format(start)} – ${format(end)}`;
    }

    // A half-made selection is worth showing, so the picker does not look stuck.
    return start ? format(start) : props.placeholder || trans('common.date.range_placeholder');
});

const hasRange = computed(() => range.value.start != null && range.value.end != null);

function onUpdate(value: DateRange | undefined, close: () => void): void {
    const next = value;

    range.value = { start: next?.start, end: next?.end };

    if (next?.start && next?.end) {
        emit('update:range', { from: next.start.toString(), to: next.end.toString() });
        close();
    }
}

function clear(): void {
    range.value = { start: undefined, end: undefined };
    emit('update:range', { from: '', to: '' });
}

const defaultPlaceholder = today(getLocalTimeZone());

defineExpose({ clear });
</script>

<template>
    <label class="block space-y-2">
        <span v-if="label" class="text-sm font-medium text-foreground">{{ label }}</span>

        <Popover v-slot="{ close }">
            <PopoverTrigger as-child>
                <Button
                    variant="outline"
                    :class="
                        cn(
                            'w-full justify-start text-left font-normal',
                            !hasRange && 'text-muted-foreground',
                        )
                    "
                >
                    <CalendarIcon class="mr-2 size-4 shrink-0" />
                    <span class="truncate">{{ buttonLabel }}</span>
                </Button>
            </PopoverTrigger>
            <PopoverContent class="w-auto p-0" align="start">
                <RangeCalendar
                    :model-value="range"
                    :default-placeholder="defaultPlaceholder"
                    initial-focus
                    @update:model-value="(value) => onUpdate(value, close)"
                />
                <div v-if="hasRange" class="border-t p-2">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="w-full"
                        @click="
                            clear();
                            close();
                        "
                    >
                        {{ trans('common.date.clear_range') }}
                    </Button>
                </div>
            </PopoverContent>
        </Popover>
    </label>
</template>
