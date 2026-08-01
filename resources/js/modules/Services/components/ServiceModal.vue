<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import ColorPicker from '@/components/ColorPicker.vue';
import { Button } from '@/components/ui/button';
import { CurrencyInput } from '@/components/ui/currency-input';
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
import { store as storeService, update as updateService } from '@/routes/services';
import type { CurrencyOption, Service } from '../types';

const props = withDefaults(
    defineProps<{ open: boolean; currencyOptions: CurrencyOption[]; entry?: Service | null }>(),
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
        default_price: props.entry?.default_price ?? '',
        currency_id:
            props.entry?.currency_id?.toString() ?? (props.currencyOptions[0]?.value ?? ''),
        color: props.entry?.color ?? 'zinc',
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

// Drives the price field: the symbol prefix, and how many decimals it takes.
const selectedCurrency = computed(() =>
    props.currencyOptions.find((option) => option.value === form.currency_id),
);

function closeModal(): void {
    emit('update:open', false);
}

function submit(): void {
    const options = { preserveScroll: true, onSuccess: () => closeModal() };

    // A blank price means "quoted per job"; the currency goes with it so the
    // server is not asked to validate a currency for a price that is not there.
    const payload = (data: Record<string, unknown>) => ({
        ...data,
        default_price: data.default_price === '' ? null : data.default_price,
        currency_id: data.default_price === '' ? null : data.currency_id,
    });

    if (isEdit.value && props.entry) {
        form.transform((data) => ({ ...payload(data), _method: 'patch' })).post(
            updateService(props.entry.id).url,
            options,
        );

        return;
    }

    form.transform(payload).post(storeService().url, options);
}
</script>

<template>
    <Dialog v-model:open="dialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{
                    isEdit ? $t('services.modal.edit_title') : $t('services.modal.create_title')
                }}</DialogTitle>
                <DialogDescription>{{ $t('services.modal.description') }}</DialogDescription>
            </DialogHeader>

            <Form class="gap-3" @submit.prevent="submit">
                <FormGroup>
                    <FormLabel for="service-name">{{ $t('services.modal.name_label') }}</FormLabel>
                    <Input
                        id="service-name"
                        v-model="form.name"
                        :placeholder="$t('services.modal.name_placeholder')"
                    />
                    <FormError :message="form.errors.name" />
                </FormGroup>

                <div class="grid gap-3 sm:grid-cols-2">
                    <FormGroup>
                        <FormLabel for="service-price">{{
                            $t('services.modal.price_label')
                        }}</FormLabel>
                        <CurrencyInput
                            id="service-price"
                            v-model="form.default_price"
                            name="default_price"
                            :symbol="selectedCurrency?.symbol"
                            :code="selectedCurrency?.code"
                        />
                        <FormError :message="form.errors.default_price" />
                    </FormGroup>

                    <FormGroup>
                        <FormLabel for="service-currency">{{
                            $t('services.modal.currency_label')
                        }}</FormLabel>
                        <Select v-model="form.currency_id">
                            <SelectTrigger id="service-currency">
                                <SelectValue :placeholder="$t('common.actions.select')" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in currencyOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <FormError :message="form.errors.currency_id" />
                    </FormGroup>
                </div>

                <p class="-mt-1 text-xs text-muted-foreground">
                    {{ $t('services.modal.price_hint') }}
                </p>

                <FormGroup>
                    <FormLabel>{{ $t('services.modal.color_label') }}</FormLabel>
                    <ColorPicker v-model="form.color" />
                    <FormError :message="form.errors.color" />
                </FormGroup>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="closeModal">{{
                        $t('common.actions.cancel')
                    }}</Button>
                    <Button type="submit" :disabled="form.processing">{{
                        $t('common.actions.save')
                    }}</Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
