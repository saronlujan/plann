<script setup lang="ts">
import { computed } from 'vue';
import { CalendarIcon } from '@lucide/vue';
import {
    DateFormatter,
    getLocalTimeZone,
    parseDate,
    today,
} from '@internationalized/date';
import { Calendar } from '@/components/ui/calendar';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        modelValue?: string | null;
        label?: string;
        hint?: string;
    }>(),
    {
        modelValue: null,
        label: 'Data',
        hint: '',
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const formatter = new DateFormatter('pt-BR', {
    dateStyle: 'long',
});

const calendarValue = computed({
    get() {
        return props.modelValue ? parseDate(props.modelValue) : undefined;
    },
    set(value) {
        emit('update:modelValue', value ? value.toString() : '');
    },
});

const buttonLabel = computed(() => {
    return calendarValue.value
        ? formatter.format(calendarValue.value.toDate(getLocalTimeZone()))
        : 'Selecionar data';
});

const defaultPlaceholder = today(getLocalTimeZone());
</script>

<template>
    <label class="block space-y-2">
        <span class="text-sm font-medium text-zinc-700">{{ label }}</span>

        <Popover v-slot="{ close }">
            <PopoverTrigger as-child>
                <Button
                    variant="outline"
                    :class="cn(
                        'w-full justify-start text-left font-normal',
                        !calendarValue && 'text-muted-foreground',
                    )"
                >
                    <CalendarIcon class="mr-2 size-4" />
                    {{ buttonLabel }}
                </Button>
            </PopoverTrigger>
            <PopoverContent class="w-auto p-0" align="start">
                <Calendar
                    v-model="calendarValue"
                    :default-placeholder="defaultPlaceholder"
                    initial-focus
                    @update:model-value="close"
                />
            </PopoverContent>
        </Popover>

        <p v-if="hint" class="text-xs text-zinc-500">
            {{ hint }}
        </p>
    </label>
</template>
