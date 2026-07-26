<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import ColorPicker from '@/components/ColorPicker.vue';
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
import { store as storeCategory, update as updateCategory } from '@/routes/categories';

type Option = { value: string; label: string };
type Category = { id: number; name: string; type: string; color: string };

const props = withDefaults(
    defineProps<{ open: boolean; typeOptions: Option[]; entry?: Category | null }>(),
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
        type: props.entry?.type ?? 'expense',
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

function closeModal(): void {
    emit('update:open', false);
}

function submit(): void {
    const options = { preserveScroll: true, onSuccess: () => closeModal() };

    if (isEdit.value && props.entry) {
        form.transform((data) => ({ ...data, _method: 'patch' })).post(
            updateCategory(props.entry.id).url,
            options,
        );

        return;
    }

    form.transform((data) => data).post(storeCategory().url, options);
}
</script>

<template>
    <Dialog v-model:open="dialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{
                    isEdit
                        ? $t('categories.modal.edit_title')
                        : $t('categories.modal.create_title')
                }}</DialogTitle>
                <DialogDescription>{{
                    $t('categories.modal.description')
                }}</DialogDescription>
            </DialogHeader>

            <Form class="gap-3" @submit.prevent="submit">
                <FormGroup>
                    <FormLabel for="cat-name">{{
                        $t('categories.modal.name_label')
                    }}</FormLabel>
                    <Input
                        id="cat-name"
                        v-model="form.name"
                        :placeholder="$t('categories.modal.name_placeholder')"
                    />
                    <FormError :message="form.errors.name" />
                </FormGroup>

                <FormGroup>
                    <FormLabel for="cat-type">{{
                        $t('categories.modal.type_label')
                    }}</FormLabel>
                    <Select v-model="form.type">
                        <SelectTrigger id="cat-type">
                            <SelectValue :placeholder="$t('common.actions.select')" />
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
                    <FormLabel>{{ $t('categories.modal.color_label') }}</FormLabel>
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
