<script setup lang="ts">
import { FileText, ImageIcon, Paperclip, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { cn } from '@/lib/utils';

/**
 * Drop zone for a receipt.
 *
 * The native file input shows a grey "no file chosen" and a button the browser
 * styles its own way — it neither matches the form nor says what is accepted.
 * This states both, and takes a dropped file.
 */
const props = defineProps<{
    modelValue: File | null;
    /** Name of the receipt already on the transaction, if any. */
    storedName?: string | null;
    storedUrl?: string;
    accept: string;
}>();

const emit = defineEmits<{ 'update:modelValue': [value: File | null] }>();

const inputRef = ref<HTMLInputElement | null>(null);
const isDraggingOver = ref(false);

const isImage = computed(() => props.modelValue?.type.startsWith('image/') ?? false);

/** Rounded to whole units: nobody needs three decimals of a receipt. */
const fileSize = computed(() => {
    const bytes = props.modelValue?.size ?? 0;

    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${Math.round(bytes / 1024)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
});

function openPicker(): void {
    inputRef.value?.click();
}

function pick(file: File | null): void {
    emit('update:modelValue', file);
}

function onInputChange(event: Event): void {
    pick((event.target as HTMLInputElement).files?.[0] ?? null);
}

function onDrop(event: DragEvent): void {
    isDraggingOver.value = false;
    pick(event.dataTransfer?.files?.[0] ?? null);
}

function clear(): void {
    pick(null);

    // Cleared by hand: choosing the same file twice fires no change event
    // otherwise, so the picker would look dead.
    if (inputRef.value) {
        inputRef.value.value = '';
    }
}
</script>

<template>
    <div class="flex flex-col gap-2">
        <!-- The chosen file replaces the zone: two states at once would be noise. -->
        <div
            v-if="props.modelValue"
            class="flex items-center gap-3 rounded-lg border bg-muted/40 p-3"
        >
            <span
                class="flex size-9 shrink-0 items-center justify-center rounded-md bg-background text-muted-foreground"
            >
                <ImageIcon v-if="isImage" class="size-4" />
                <FileText v-else class="size-4" />
            </span>

            <span class="flex min-w-0 flex-1 flex-col">
                <span class="truncate text-sm font-medium">{{ props.modelValue.name }}</span>
                <span class="text-xs text-muted-foreground">{{ fileSize }}</span>
            </span>

            <button
                type="button"
                class="shrink-0 rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-background hover:text-foreground"
                :aria-label="$t('common.actions.remove')"
                @click="clear"
            >
                <X class="size-4" />
            </button>
        </div>

        <template v-else>
            <!-- What is already attached, before offering to replace it. -->
            <a
                v-if="props.storedName"
                :href="props.storedUrl"
                class="flex items-center gap-2 text-sm font-medium text-primary underline underline-offset-4"
            >
                <Paperclip class="size-4 shrink-0" />
                <span class="truncate">{{ $t('transactions.actions.open_attachment') }}</span>
            </a>

            <button
                type="button"
                :class="
                    cn(
                        'flex w-full flex-col items-center gap-1 rounded-lg border border-dashed px-4 py-5 text-center transition-colors',
                        isDraggingOver
                            ? 'border-primary bg-primary/5'
                            : 'border-input hover:border-muted-foreground/50 hover:bg-muted/40',
                    )
                "
                @click="openPicker"
                @dragover.prevent="isDraggingOver = true"
                @dragleave="isDraggingOver = false"
                @drop.prevent="onDrop"
            >
                <Paperclip class="size-5 text-muted-foreground" />
                <span class="text-sm font-medium">
                    {{
                        props.storedName
                            ? $t('transactions.attachment.replace')
                            : $t('transactions.attachment.choose')
                    }}
                </span>
                <span class="text-xs text-muted-foreground">
                    {{ $t('transactions.attachment.formats') }}
                </span>
            </button>
        </template>

        <input
            ref="inputRef"
            type="file"
            class="hidden"
            :accept="props.accept"
            @change="onInputChange"
        />
    </div>
</template>
