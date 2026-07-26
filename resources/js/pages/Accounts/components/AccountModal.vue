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
import { store as storeAccount, update as updateAccount } from '@/routes/accounts';

type Option = { value: string; label: string };
type Account = {
    id: number;
    name: string;
    kind: string;
    currency_id: number;
    balance: string;
    credit_limit: string | null;
    closing_day: number | null;
    due_day: number | null;
};

const props = withDefaults(
    defineProps<{
        open: boolean;
        currencyOptions: Option[];
        kindOptions: Option[];
        entry?: Account | null;
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
        name: props.entry?.name ?? '',
        kind: props.entry?.kind ?? props.kindOptions[0]?.value ?? 'account',
        currency_id: props.entry?.currency_id.toString() ?? props.currencyOptions[0]?.value ?? '',
        balance: props.entry?.balance ?? '',
        credit_limit: props.entry?.credit_limit ?? '',
        closing_day: props.entry?.closing_day?.toString() ?? '',
        due_day: props.entry?.due_day?.toString() ?? '',
    };
}

const form = useForm(buildValues());

const isCard = computed(() => form.kind === 'credit_card');

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
            updateAccount(props.entry.id).url,
            options,
        );

        return;
    }

    form.transform((data) => data).post(storeAccount().url, options);
}
</script>

<template>
    <Dialog v-model:open="dialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{
                    isEdit ? $t('accounts.modal.edit_title') : $t('accounts.modal.create_title')
                }}</DialogTitle>
                <DialogDescription>{{ $t('accounts.modal.description') }}</DialogDescription>
            </DialogHeader>

            <Form class="gap-3" @submit.prevent="submit">
                <FormGroup>
                    <FormLabel for="acc-name">{{ $t('accounts.modal.name_label') }}</FormLabel>
                    <Input
                        id="acc-name"
                        v-model="form.name"
                        :placeholder="$t('accounts.modal.name_placeholder')"
                    />
                    <FormError :message="form.errors.name" />
                </FormGroup>

                <FormGroup>
                    <FormLabel for="acc-kind">{{ $t('accounts.modal.kind_label') }}</FormLabel>
                    <Select v-model="form.kind">
                        <SelectTrigger id="acc-kind">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="option in kindOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <FormError :message="form.errors.kind" />
                </FormGroup>

                <!--
                    With a single active currency there is nothing to choose: the form
                    already defaults to it, so the field would only be a read-only echo.
                -->
                <FormGroup v-if="currencyOptions.length > 1">
                    <FormLabel for="acc-currency">{{
                        $t('accounts.modal.currency_label')
                    }}</FormLabel>
                    <Select v-model="form.currency_id">
                        <SelectTrigger id="acc-currency">
                            <SelectValue :placeholder="$t('accounts.modal.currency_placeholder')" />
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

                <FormGroup v-if="!isCard">
                    <FormLabel for="acc-balance">{{
                        $t('accounts.modal.balance_label')
                    }}</FormLabel>
                    <Input
                        id="acc-balance"
                        v-model="form.balance"
                        type="number"
                        step="0.01"
                        :placeholder="$t('accounts.modal.balance_placeholder')"
                    />
                    <FormError :message="form.errors.balance" />
                </FormGroup>

                <template v-if="isCard">
                    <FormGroup>
                        <FormLabel for="acc-limit">{{
                            $t('accounts.modal.credit_limit_label')
                        }}</FormLabel>
                        <Input
                            id="acc-limit"
                            v-model="form.credit_limit"
                            type="number"
                            step="0.01"
                            :placeholder="$t('accounts.modal.credit_limit_placeholder')"
                        />
                        <FormError :message="form.errors.credit_limit" />
                    </FormGroup>

                    <div class="grid grid-cols-2 gap-3">
                        <FormGroup>
                            <FormLabel for="acc-closing">{{
                                $t('accounts.modal.closing_day_label')
                            }}</FormLabel>
                            <Input
                                id="acc-closing"
                                v-model="form.closing_day"
                                type="number"
                                min="1"
                                max="31"
                                :placeholder="$t('accounts.modal.day_placeholder')"
                            />
                            <FormError :message="form.errors.closing_day" />
                        </FormGroup>

                        <FormGroup>
                            <FormLabel for="acc-due">{{
                                $t('accounts.modal.due_day_label')
                            }}</FormLabel>
                            <Input
                                id="acc-due"
                                v-model="form.due_day"
                                type="number"
                                min="1"
                                max="31"
                                :placeholder="$t('accounts.modal.day_placeholder')"
                            />
                            <FormError :message="form.errors.due_day" />
                        </FormGroup>
                    </div>
                </template>

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
