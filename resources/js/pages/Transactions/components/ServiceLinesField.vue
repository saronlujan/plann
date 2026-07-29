<script setup lang="ts">
import { PlusIcon, Trash2Icon } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { CurrencyInput } from '@/components/ui/currency-input';
import { FormError } from '@/components/ui/form';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { colorHex } from '@/lib/labelColors';
import type { ServiceLine, ServiceOption } from '../types';

const props = defineProps<{
    modelValue: ServiceLine[];
    serviceOptions: ServiceOption[];
    /** Which currency the entry is in; a price quoted in another one is not offered. */
    currencyId: string;
    symbol?: string;
    code?: string;
    errors?: Record<string, string>;
}>();

const emit = defineEmits<{ 'update:modelValue': [value: ServiceLine[]] }>();

const lines = computed(() => props.modelValue);

/** Services already on the entry, so the same one is never offered twice. */
const takenIds = computed(
    () => new Set(lines.value.map((line) => line.service_id).filter((id) => id !== null)),
);

const availableOptions = computed(() =>
    props.serviceOptions.filter((service) => !takenIds.value.has(service.id)),
);

function optionsFor(line: ServiceLine): ServiceOption[] {
    const current = props.serviceOptions.find((service) => service.id === line.service_id);

    return current ? [current, ...availableOptions.value] : availableOptions.value;
}

function nameFor(line: ServiceLine): string | null {
    return props.serviceOptions.find((service) => service.id === line.service_id)?.name ?? null;
}

/**
 * The price a service is offered at, but only when it is quoted in the currency
 * this entry is in — converting would need a rate for the day, and there is none.
 */
function suggestedPrice(service: ServiceOption): string {
    const matchesCurrency = service.currency_id?.toString() === props.currencyId;

    return matchesCurrency && service.default_price !== null ? service.default_price : '';
}

function update(next: ServiceLine[]): void {
    emit('update:modelValue', next);
}

function appendLine(): void {
    const service = availableOptions.value[0];

    update([
        ...lines.value,
        {
            service_id: service?.id ?? null,
            amount: service ? suggestedPrice(service) : '',
        },
    ]);
}

function removeLine(index: number): void {
    update(lines.value.filter((_, position) => position !== index));
}

function setService(index: number, value: string): void {
    const service = props.serviceOptions.find((option) => option.id.toString() === value);

    update(
        lines.value.map((line, position) => {
            if (position !== index) {
                return line;
            }

            // Picking a service replaces the amount with what that service is
            // offered at — the line now stands for something else, so carrying
            // the previous price over would quietly misprice it. A service with
            // no standing price leaves the field empty to be filled in.
            return {
                service_id: service?.id ?? null,
                amount: service ? suggestedPrice(service) : '',
            };
        }),
    );
}

function setAmount(index: number, value: string): void {
    update(
        lines.value.map((line, position) =>
            position === index ? { ...line, amount: value } : line,
        ),
    );
}
</script>

<template>
    <div class="flex flex-col gap-2">
        <div v-for="(line, index) in lines" :key="index" class="flex flex-col gap-1">
            <div class="flex items-start gap-2">
                <!--
                    A line whose service was retired has nothing to pick from, so it
                    shows what it is and keeps its amount rather than disappearing.
                -->
                <div
                    v-if="line.service_id === null"
                    class="flex h-9 flex-1 items-center gap-2 rounded-md border border-dashed px-3 text-sm text-muted-foreground"
                >
                    {{ $t('transactions.services.unattributed') }}
                </div>
                <Select
                    v-else
                    :model-value="line.service_id?.toString() ?? ''"
                    @update:model-value="(value) => setService(index, value as string)"
                >
                    <SelectTrigger class="flex-1">
                        <SelectValue :placeholder="$t('common.actions.select')" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in optionsFor(line)"
                            :key="option.id"
                            :value="option.id.toString()"
                        >
                            <span class="flex items-center gap-2">
                                <span
                                    class="size-2.5 shrink-0 rounded-full"
                                    :style="{ backgroundColor: colorHex(option.color) }"
                                />
                                {{ option.name }}
                            </span>
                        </SelectItem>
                    </SelectContent>
                </Select>

                <CurrencyInput
                    :model-value="line.amount"
                    class="w-32"
                    :symbol="symbol"
                    :code="code"
                    :aria-label="nameFor(line) ?? $t('transactions.services.unattributed')"
                    @update:model-value="(value) => setAmount(index, value)"
                />

                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    :aria-label="$t('transactions.services.remove')"
                    @click="removeLine(index)"
                >
                    <Trash2Icon class="size-4" />
                </Button>
            </div>
            <FormError :message="errors?.[`services.${index}.amount`]" />
            <FormError :message="errors?.[`services.${index}.service_id`]" />
        </div>

        <Button
            type="button"
            variant="outline"
            size="sm"
            class="self-start"
            :disabled="availableOptions.length === 0"
            @click="appendLine"
        >
            <PlusIcon class="size-4" />
            {{ $t('transactions.services.add') }}
        </Button>
    </div>
</template>
