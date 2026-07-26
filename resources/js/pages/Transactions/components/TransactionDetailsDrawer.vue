<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Drawer,
    DrawerClose,
    DrawerContent,
    DrawerDescription,
    DrawerFooter,
    DrawerHeader,
    DrawerTitle,
} from '@/components/ui/drawer';
import { colorHex } from '@/lib/labelColors';
import type { CategoryOption, TagOption, TransactionEntry } from '../types';
import { amountClass, dueStatus, movementBadge, scheduleLabel, signedAmount } from '../display';
import { formatCurrency, formatDate } from '../format';

const props = defineProps<{
    open: boolean;
    entry?: TransactionEntry | null;
    categoryOptions: CategoryOption[];
    tagOptions: TagOption[];
}>();

const emit = defineEmits<{ 'update:open': [value: boolean] }>();

const dialogOpen = computed({
    get: () => props.open,
    set: (value: boolean) => emit('update:open', value),
});

const category = computed(() =>
    props.entry?.category_id != null
        ? (props.categoryOptions.find((option) => option.id === props.entry?.category_id) ?? null)
        : null,
);

const tags = computed(() =>
    props.tagOptions.filter((option) => props.entry?.tag_ids?.includes(option.id)),
);

const statusText = computed(() => {
    const entry = props.entry;

    if (!entry) {
        return '';
    }

    if (entry.paid_at) {
        return `Pago em ${formatDate(entry.paid_at)}`;
    }

    const due = dueStatus(entry);

    if (due === 'overdue') {
        return 'Vencida';
    }

    if (due === 'soon') {
        return 'Vence em breve';
    }

    return 'Em aberto';
});

const hasAdjustment = computed(
    () => props.entry != null && Number.parseFloat(props.entry.adjustment_amount) !== 0,
);
</script>

<template>
    <Drawer v-model:open="dialogOpen" direction="right">
        <DrawerContent v-if="entry">
            <DrawerHeader class="gap-2 text-left">
                <div class="flex items-center gap-2">
                    <Badge variant="outline" :class="movementBadge[entry.movement_type]?.class">
                        {{ movementBadge[entry.movement_type]?.label ?? entry.movement_type }}
                    </Badge>
                    <Badge variant="outline" class="rounded-full">
                        {{ scheduleLabel(entry) }}
                    </Badge>
                </div>
                <DrawerTitle class="text-xl">{{ entry.label }}</DrawerTitle>
                <DrawerDescription>{{ entry.description }}</DrawerDescription>
            </DrawerHeader>

            <div class="flex-1 overflow-y-auto px-4 pb-4">
                <div class="mb-4 text-2xl font-semibold" :class="amountClass(entry)">
                    {{
                        formatCurrency(signedAmount(entry), entry.currency_symbol, { signed: true })
                    }}
                    <span class="text-sm font-normal text-muted-foreground">
                        {{ entry.currency_code }}
                    </span>
                </div>

                <dl class="divide-y text-sm">
                    <div class="flex items-center justify-between gap-4 py-2">
                        <dt class="text-muted-foreground">Status</dt>
                        <dd class="font-medium">{{ statusText }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 py-2">
                        <dt class="text-muted-foreground">Data</dt>
                        <dd class="font-medium">{{ formatDate(entry.date) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 py-2">
                        <dt class="text-muted-foreground">Conta</dt>
                        <dd class="font-medium">{{ entry.source }}</dd>
                    </div>
                    <div
                        v-if="entry.schedule_type === 'installment'"
                        class="flex items-center justify-between gap-4 py-2"
                    >
                        <dt class="text-muted-foreground">Parcela</dt>
                        <dd class="font-medium">
                            {{ entry.installment_number ?? 1 }}/{{ entry.installments_total ?? 1 }}
                        </dd>
                    </div>
                    <div v-if="hasAdjustment" class="flex items-center justify-between gap-4 py-2">
                        <dt class="text-muted-foreground">Ajuste</dt>
                        <dd class="font-medium">
                            {{ formatCurrency(entry.adjustment_amount, entry.currency_symbol) }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 py-2">
                        <dt class="text-muted-foreground">Categoria</dt>
                        <dd class="flex items-center gap-2 font-medium">
                            <template v-if="category">
                                <span
                                    class="size-2.5 rounded-full"
                                    :style="{ backgroundColor: colorHex(category.color) }"
                                />
                                {{ category.name }}
                            </template>
                            <span v-else class="text-muted-foreground">—</span>
                        </dd>
                    </div>
                    <div class="flex items-start justify-between gap-4 py-2">
                        <dt class="text-muted-foreground">Tags</dt>
                        <dd class="flex flex-wrap justify-end gap-1.5">
                            <Badge
                                v-for="tag in tags"
                                :key="tag.id"
                                variant="outline"
                                class="rounded-full"
                            >
                                <span
                                    class="mr-1 size-2 rounded-full"
                                    :style="{ backgroundColor: colorHex(tag.color) }"
                                />
                                {{ tag.name }}
                            </Badge>
                            <span v-if="tags.length === 0" class="text-muted-foreground">—</span>
                        </dd>
                    </div>
                </dl>
            </div>

            <DrawerFooter>
                <DrawerClose as-child>
                    <Button variant="outline">Fechar</Button>
                </DrawerClose>
            </DrawerFooter>
        </DrawerContent>
    </Drawer>
</template>
