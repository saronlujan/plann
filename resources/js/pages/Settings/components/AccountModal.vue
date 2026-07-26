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
import { store as storeAccount, update as updateAccount } from '@/routes/settings/accounts';

type Option = { value: string; label: string };
type Account = { id: number; name: string; currency_id: number; balance: string };

const props = withDefaults(
    defineProps<{ open: boolean; currencyOptions: Option[]; entry?: Account | null }>(),
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
        currency_id: props.entry?.currency_id.toString() ?? props.currencyOptions[0]?.value ?? '',
        balance: props.entry?.balance ?? '',
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
        form.transform((data) => ({ ...data, _method: 'patch' })).post(updateAccount(props.entry.id).url, options);

        return;
    }

    form.transform((data) => data).post(storeAccount().url, options);
}
</script>

<template>
    <Dialog v-model:open="dialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ isEdit ? 'Editar conta' : 'Nova conta' }}</DialogTitle>
                <DialogDescription>Contas em moedas ativas.</DialogDescription>
            </DialogHeader>

            <Form class="gap-3" @submit.prevent="submit">
                <FormGroup>
                    <FormLabel for="acc-name">Nome</FormLabel>
                    <Input id="acc-name" v-model="form.name" placeholder="Ex.: Conta Corrente" />
                    <FormError :message="form.errors.name" />
                </FormGroup>

                <FormGroup>
                    <FormLabel for="acc-currency">Moeda</FormLabel>
                    <Select v-model="form.currency_id">
                        <SelectTrigger id="acc-currency">
                            <SelectValue placeholder="Selecione a moeda" />
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

                <FormGroup>
                    <FormLabel for="acc-balance">Saldo inicial</FormLabel>
                    <Input
                        id="acc-balance"
                        v-model="form.balance"
                        type="number"
                        step="0.01"
                        placeholder="0,00"
                    />
                    <FormError :message="form.errors.balance" />
                </FormGroup>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="closeModal">Cancelar</Button>
                    <Button type="submit" :disabled="form.processing">Salvar</Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
