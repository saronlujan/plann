<script setup lang="ts">
import { computed } from 'vue';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';

const props = defineProps<{
    open: boolean;
    title?: string;
    description?: string;
    confirmLabel?: string;
    cancelLabel?: string;
}>();

const emit = defineEmits<{ 'update:open': [value: boolean]; confirm: [] }>();

const model = computed({
    get: () => props.open,
    set: (value: boolean) => emit('update:open', value),
});
</script>

<template>
    <AlertDialog v-model:open="model">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>{{ title ?? $t('common.confirm.title') }}</AlertDialogTitle>
                <AlertDialogDescription>
                    {{ description ?? $t('common.confirm.description') }}
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>{{
                    cancelLabel ?? $t('common.confirm.cancel')
                }}</AlertDialogCancel>
                <AlertDialogAction
                    class="bg-destructive text-white hover:bg-destructive/90 focus-visible:ring-destructive/20 dark:focus-visible:ring-destructive/40"
                    @click="emit('confirm')"
                >
                    {{ confirmLabel ?? $t('common.confirm.confirm') }}
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
