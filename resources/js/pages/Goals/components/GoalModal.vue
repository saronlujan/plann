<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { Button } from '@/components/ui/button';
import DatePicker from '@/components/ui/date-picker/DatePicker.vue';
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
import { store as storeGoal, update as updateGoal } from '@/routes/goals';

type CurrencyOption = { id: number; code: string; name: string };
type Goal = {
    id: number;
    name: string;
    currency_id: number;
    target_amount: string;
    current_amount: string;
    target_date: string | null;
};

const props = withDefaults(
    defineProps<{ open: boolean; currencyOptions: CurrencyOption[]; entry?: Goal | null }>(),
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
        name: props.entry?.name ?? '',
        currency_id:
            props.entry?.currency_id.toString() ?? props.currencyOptions[0]?.id.toString() ?? '',
        target_amount: props.entry?.target_amount ?? '',
        current_amount: props.entry?.current_amount ?? '',
        target_date: props.entry?.target_date ?? '',
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
            updateGoal(props.entry.id).url,
            options,
        );

        return;
    }

    form.transform((data) => data).post(storeGoal().url, options);
}
</script>

<template>
    <Dialog v-model:open="dialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>
                    {{ isEdit ? $t('goals.modal.edit_title') : $t('goals.modal.create_title') }}
                </DialogTitle>
                <DialogDescription>{{ $t('goals.modal.description') }}</DialogDescription>
            </DialogHeader>

            <Form class="gap-3" @submit.prevent="submit">
                <FormGroup>
                    <FormLabel for="g-name">{{ $t('goals.modal.name_label') }}</FormLabel>
                    <Input
                        id="g-name"
                        v-model="form.name"
                        :placeholder="$t('goals.modal.name_placeholder')"
                    />
                    <FormError :message="form.errors.name" />
                </FormGroup>

                <div class="grid gap-3 sm:grid-cols-2">
                    <FormGroup>
                        <FormLabel for="g-target">{{ $t('goals.modal.target_label') }}</FormLabel>
                        <Input
                            id="g-target"
                            v-model="form.target_amount"
                            type="number"
                            step="0.01"
                            min="0"
                        />
                        <FormError :message="form.errors.target_amount" />
                    </FormGroup>
                    <FormGroup>
                        <FormLabel for="g-current">{{ $t('goals.modal.current_label') }}</FormLabel>
                        <Input
                            id="g-current"
                            v-model="form.current_amount"
                            type="number"
                            step="0.01"
                            min="0"
                        />
                        <FormError :message="form.errors.current_amount" />
                    </FormGroup>
                </div>

                <FormGroup>
                    <FormLabel for="g-currency">{{ $t('goals.modal.currency_label') }}</FormLabel>
                    <Select v-model="form.currency_id">
                        <SelectTrigger id="g-currency">
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
                    <DatePicker
                        v-model="form.target_date"
                        :label="$t('goals.modal.target_date_label')"
                    />
                    <FormError :message="form.errors.target_date" />
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
