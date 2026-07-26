<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Form, FormError, FormGroup, FormLabel } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { colorHex } from '@/lib/labelColors';
import { store as storeBudget, update as updateBudget } from '@/routes/budgets';

type CategoryOption = { id: number; name: string; color: string };
type CurrencyOption = { id: number; code: string; name: string };
type Budget = { id: number; category_id: number; currency_id: number; amount: string };

const props = withDefaults(
    defineProps<{
        open: boolean;
        categoryOptions: CategoryOption[];
        currencyOptions: CurrencyOption[];
        entry?: Budget | null;
    }>(),
    { entry: null },
);

const emit = defineEmits<{ 'update:open': [value: boolean] }>();

const dialogOpen = computed({
    get: () => props.open,
    set: (value: boolean) => emit('update:open', value),
});

const isEdit = computed(() => props.entry != null);

function buildValues() {
    return {
        category_id:
            props.entry?.category_id.toString() ?? props.categoryOptions[0]?.id.toString() ?? '',
        currency_id:
            props.entry?.currency_id.toString() ?? props.currencyOptions[0]?.id.toString() ?? '',
        amount: props.entry?.amount ?? '',
    };
}

const form = useForm(buildValues());

watch(
    () => [props.open, props.entry] as const,
    ([open]) => {
        if (!open) {
            return;
        }

        form.defaults(buildValues());
        form.reset();
        form.clearErrors();
    },
    { immediate: true },
);

function closeModal(): void {
    emit('update:open', false);
}

function submit(): void {
    const options = { preserveScroll: true, onSuccess: () => closeModal() };

    if (isEdit.value && props.entry) {
        form.transform((data) => ({ ...data, _method: 'patch' })).post(
            updateBudget(props.entry.id).url,
            options,
        );

        return;
    }

    form.transform((data) => data).post(storeBudget().url, options);
}
</script>

<template>
    <Dialog v-model:open="dialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>
                    {{ isEdit ? $t('budgets.modal.edit_title') : $t('budgets.modal.create_title') }}
                </DialogTitle>
                <DialogDescription>{{ $t('budgets.modal.description') }}</DialogDescription>
            </DialogHeader>

            <Form class="gap-3" @submit.prevent="submit">
                <FormGroup>
                    <FormLabel for="b-category">{{ $t('budgets.modal.category_label') }}</FormLabel>
                    <Select v-model="form.category_id">
                        <SelectTrigger id="b-category">
                            <SelectValue :placeholder="$t('budgets.modal.category_placeholder')" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="option in categoryOptions"
                                :key="option.id"
                                :value="option.id.toString()"
                            >
                                <span class="flex items-center gap-2">
                                    <span
                                        class="size-2.5 rounded-full"
                                        :style="{ backgroundColor: colorHex(option.color) }"
                                    />
                                    {{ option.name }}
                                </span>
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <FormError :message="form.errors.category_id" />
                </FormGroup>

                <FormGroup>
                    <FormLabel for="b-currency">{{ $t('budgets.modal.currency_label') }}</FormLabel>
                    <Select v-model="form.currency_id">
                        <SelectTrigger id="b-currency">
                            <SelectValue :placeholder="$t('common.actions.select')" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="option in currencyOptions"
                                :key="option.id"
                                :value="option.id.toString()"
                            >
                                {{ option.code }} - {{ option.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <FormError :message="form.errors.currency_id" />
                </FormGroup>

                <FormGroup>
                    <FormLabel for="b-amount">{{ $t('budgets.modal.amount_label') }}</FormLabel>
                    <Input
                        id="b-amount"
                        v-model="form.amount"
                        type="number"
                        step="0.01"
                        min="0"
                        :placeholder="$t('budgets.modal.amount_placeholder')"
                    />
                    <FormError :message="form.errors.amount" />
                </FormGroup>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="closeModal">
                        {{ $t('common.actions.cancel') }}
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ $t('common.actions.save') }}
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
