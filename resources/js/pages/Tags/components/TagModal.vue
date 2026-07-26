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
import { store as storeTag, update as updateTag } from '@/routes/tags';

type Tag = { id: number; name: string; color: string };

const props = withDefaults(defineProps<{ open: boolean; entry?: Tag | null }>(), { entry: null });

const emit = defineEmits<{ 'update:open': [value: boolean] }>();

const dialogOpen = computed({
    get: () => props.open,
    set: (value: boolean) => emit('update:open', value),
});

const isEdit = computed(() => props.entry != null);

function buildValues() {
    return {
        name: props.entry?.name ?? '',
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
            updateTag(props.entry.id).url,
            options,
        );

        return;
    }

    form.transform((data) => data).post(storeTag().url, options);
}
</script>

<template>
    <Dialog v-model:open="dialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{
                    isEdit
                        ? $t('tags.modal.edit_title')
                        : $t('tags.modal.create_title')
                }}</DialogTitle>
                <DialogDescription>{{ $t('tags.modal.description') }}</DialogDescription>
            </DialogHeader>

            <Form class="gap-3" @submit.prevent="submit">
                <FormGroup>
                    <FormLabel for="tag-name">{{ $t('tags.modal.name_label') }}</FormLabel>
                    <Input
                        id="tag-name"
                        v-model="form.name"
                        :placeholder="$t('tags.modal.name_placeholder')"
                    />
                    <FormError :message="form.errors.name" />
                </FormGroup>

                <FormGroup>
                    <FormLabel>{{ $t('tags.modal.color_label') }}</FormLabel>
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
