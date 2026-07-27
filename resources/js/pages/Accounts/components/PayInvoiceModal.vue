<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
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
import { payInvoice } from '@/routes/accounts';

type Option = { value: string; label: string };

const props = defineProps<{
    open: boolean;
    accountId: number;
    payAccounts: Option[];
    currencyCode: string;
    suggestedAmount: string;
    today: string;
}>();

const emit = defineEmits<{ 'update:open': [value: boolean] }>();

const dialogOpen = computed({
    get: () => props.open,
    set: (value: boolean) => emit('update:open', value),
});

const form = useForm({
    account_id: props.payAccounts[0]?.value ?? '',
    amount: props.suggestedAmount,
    effective_date: props.today,
});

watch(
    () => props.open,
    (open) => {
        if (!open) {
            return;
        }

        form.defaults({
            account_id: props.payAccounts[0]?.value ?? '',
            amount: props.suggestedAmount,
            effective_date: props.today,
        });
        form.reset();
        form.clearErrors();
    },
);

function closeModal(): void {
    emit('update:open', false);
}

function submit(): void {
    form.post(payInvoice(props.accountId).url, {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
}
</script>

<template>
    <Dialog v-model:open="dialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ $t('accounts.invoice.pay.title') }}</DialogTitle>
                <DialogDescription>{{ $t('accounts.invoice.pay.description') }}</DialogDescription>
            </DialogHeader>

            <p v-if="payAccounts.length === 0" class="text-sm text-muted-foreground">
                {{ $t('accounts.invoice.pay.no_accounts') }}
            </p>

            <Form v-else class="gap-3" @submit.prevent="submit">
                <FormGroup>
                    <FormLabel for="pay-account">{{
                        $t('accounts.invoice.pay.account_label')
                    }}</FormLabel>
                    <Select v-model="form.account_id">
                        <SelectTrigger id="pay-account">
                            <SelectValue
                                :placeholder="$t('accounts.invoice.pay.account_placeholder')"
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="option in payAccounts"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <FormError :message="form.errors.account_id" />
                </FormGroup>

                <FormGroup>
                    <FormLabel for="pay-amount">{{
                        $t('accounts.invoice.pay.amount_label')
                    }}</FormLabel>
                    <CurrencyInput
                        id="pay-amount"
                        v-model="form.amount"
                        :code="props.currencyCode"
                    />
                    <FormError :message="form.errors.amount" />
                </FormGroup>

                <FormGroup>
                    <FormLabel for="pay-date">{{
                        $t('accounts.invoice.pay.date_label')
                    }}</FormLabel>
                    <Input id="pay-date" v-model="form.effective_date" type="date" />
                    <FormError :message="form.errors.effective_date" />
                </FormGroup>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="closeModal">{{
                        $t('common.actions.cancel')
                    }}</Button>
                    <Button type="submit" :disabled="form.processing">{{
                        $t('accounts.invoice.pay.action')
                    }}</Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
