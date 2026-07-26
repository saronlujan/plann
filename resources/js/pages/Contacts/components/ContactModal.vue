<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import PhoneInput from '@/components/PhoneInput.vue';
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
import { store as storeContact, update as updateContact } from '@/routes/contacts';

type Option = { value: string; label: string };
type Contact = {
    id: number;
    name: string;
    type: string;
    email: string | null;
    phone: string | null;
    document: string | null;
    notes: string | null;
};

const props = withDefaults(
    defineProps<{ open: boolean; typeOptions: Option[]; entry?: Contact | null }>(),
    { entry: null },
);

const emit = defineEmits<{ 'update:open': [value: boolean] }>();

const dialogOpen = computed({
    get: () => props.open,
    set: (value: boolean) => emit('update:open', value),
});

const isEdit = computed(() => props.entry != null);

function buildValues() {
    const entry = props.entry;

    return {
        name: entry?.name ?? '',
        type: entry?.type ?? 'client',
        email: entry?.email ?? '',
        phone: entry?.phone ?? '',
        document: entry?.document ?? '',
        notes: entry?.notes ?? '',
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
            updateContact(props.entry.id).url,
            options,
        );

        return;
    }

    form.transform((data) => data).post(storeContact().url, options);
}

const textareaClass =
    'border-input flex w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]';
</script>

<template>
    <Dialog v-model:open="dialogOpen">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>{{
                    isEdit ? $t('contacts.modal.edit_title') : $t('contacts.modal.create_title')
                }}</DialogTitle>
                <DialogDescription>{{ $t('contacts.modal.description') }}</DialogDescription>
            </DialogHeader>

            <Form class="gap-3" @submit.prevent="submit">
                <div class="grid gap-4">
                    <FormGroup>
                        <FormLabel for="c-name">{{ $t('contacts.fields.name') }}</FormLabel>
                        <Input
                            id="c-name"
                            v-model="form.name"
                            :placeholder="$t('contacts.fields.name_placeholder')"
                        />
                        <FormError :message="form.errors.name" />
                    </FormGroup>

                    <FormGroup>
                        <FormLabel for="c-type">{{ $t('contacts.fields.type') }}</FormLabel>
                        <Select v-model="form.type">
                            <SelectTrigger id="c-type">
                                <SelectValue
                                    :placeholder="$t('contacts.fields.type_placeholder')"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in typeOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <FormError :message="form.errors.type" />
                    </FormGroup>

                    <FormGroup>
                        <FormLabel for="c-email">{{ $t('contacts.fields.email') }}</FormLabel>
                        <Input
                            id="c-email"
                            v-model="form.email"
                            type="email"
                            :placeholder="$t('contacts.fields.email_placeholder')"
                        />
                        <FormError :message="form.errors.email" />
                    </FormGroup>

                    <FormGroup>
                        <FormLabel>{{ $t('contacts.fields.phone') }}</FormLabel>
                        <PhoneInput v-model="form.phone" />
                        <FormError :message="form.errors.phone" />
                    </FormGroup>

                    <FormGroup>
                        <FormLabel for="c-notes">{{ $t('contacts.fields.notes') }}</FormLabel>
                        <textarea
                            id="c-notes"
                            v-model="form.notes"
                            rows="3"
                            :class="textareaClass"
                            :placeholder="$t('contacts.fields.notes_placeholder')"
                        ></textarea>
                        <FormError :message="form.errors.notes" />
                    </FormGroup>
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="closeModal">{{
                        $t('common.actions.cancel')
                    }}</Button>
                    <Button type="submit" :disabled="form.processing">
                        {{
                            form.processing
                                ? $t('common.actions.saving')
                                : $t('common.actions.save')
                        }}
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
