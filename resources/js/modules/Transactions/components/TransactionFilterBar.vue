<script setup lang="ts">
import { ChevronLeftIcon, ChevronRightIcon, SearchIcon } from '@lucide/vue';
import { getActiveLanguage } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { DateRangePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Option, TransactionFilters } from '../types';

const props = defineProps<{
    filters: TransactionFilters;
    scaleOptions: Option[];
}>();

const emit = defineEmits<{ apply: [patch: Record<string, string>] }>();

const search = ref(props.filters.search);

// Follows the server: a cleared filter or a back button has to be reflected.
watch(
    () => props.filters.search,
    (value) => (search.value = value),
);

// Debounced, so typing a description is one request rather than one per letter.
let pending: ReturnType<typeof setTimeout> | undefined;

watch(search, (value) => {
    clearTimeout(pending);
    pending = setTimeout(() => emit('apply', { search: value }), 350);
});

const localeTag = computed(
    () => ({ pt: 'pt-BR', en: 'en-US', es: 'es-AR' })[getActiveLanguage()] ?? 'pt-BR',
);

function day(date: string): Date {
    return new Date(`${date}T00:00:00`);
}

/**
 * Names the stretch on screen in the shortest form that is still unambiguous:
 * a single day in full, a week as its two ends, a month as month and year.
 */
const periodLabel = computed(() => {
    const short = new Intl.DateTimeFormat(localeTag.value, { day: '2-digit', month: 'short' });

    if (props.filters.scale === 'day') {
        return new Intl.DateTimeFormat(localeTag.value, {
            day: '2-digit',
            month: 'long',
            year: 'numeric',
        }).format(day(props.filters.from));
    }

    if (props.filters.scale === 'week') {
        return `${short.format(day(props.filters.from))} – ${short.format(day(props.filters.to))}`;
    }

    return new Intl.DateTimeFormat(localeTag.value, { month: 'long', year: 'numeric' }).format(
        day(props.filters.from),
    );
});

/** The arrows move the anchor by one unit of the scale; the server does the rest. */
function step(direction: -1 | 1): void {
    const anchor = day(props.filters.date);

    if (props.filters.scale === 'day') {
        anchor.setDate(anchor.getDate() + direction);
    } else if (props.filters.scale === 'week') {
        anchor.setDate(anchor.getDate() + direction * 7);
    } else {
        // Day 1 first, so stepping from the 31st cannot skip a short month.
        anchor.setDate(1);
        anchor.setMonth(anchor.getMonth() + direction);
    }

    emit('apply', { date: anchor.toISOString().slice(0, 10), from: '', to: '' });
}

</script>

<template>
    <div class="flex flex-wrap items-center gap-3">
        <!--
            The period stands down while a range is in force: two controls that
            both decide the visible stretch would only argue with each other.
        -->
        <div v-if="!filters.custom_range" class="flex items-center gap-1">
            <Button
                type="button"
                variant="outline"
                size="icon"
                :aria-label="$t('transactions.filters.previous')"
                @click="step(-1)"
            >
                <ChevronLeftIcon class="size-4" />
            </Button>

            <Select
                :model-value="filters.scale"
                @update:model-value="(v) => emit('apply', { scale: String(v), from: '', to: '' })"
            >
                <SelectTrigger class="min-w-44 justify-center font-medium">
                    <SelectValue>
                        <span class="capitalize">{{ periodLabel }}</span>
                    </SelectValue>
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="option in scaleOptions"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <Button
                type="button"
                variant="outline"
                size="icon"
                :aria-label="$t('transactions.filters.next')"
                @click="step(1)"
            >
                <ChevronRightIcon class="size-4" />
            </Button>
        </div>

        <div class="relative min-w-48 flex-1">
            <SearchIcon
                class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                aria-hidden="true"
            />
            <Input
                v-model="search"
                class="pl-9"
                :placeholder="$t('transactions.filters.search')"
                :aria-label="$t('transactions.filters.search')"
            />
        </div>

        <!--
            One control rather than two fields: the range is a single idea, and it
            only reaches the server once both ends are picked.
        -->
        <div class="w-60">
            <DateRangePicker
                :from="filters.custom_range ? filters.from : null"
                :to="filters.custom_range ? filters.to : null"
                :placeholder="$t('transactions.filters.range')"
                @update:range="(value) => emit('apply', value)"
            />
        </div>
    </div>
</template>
