<script setup lang="ts">
import { CheckIcon, XIcon } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { colorHex } from '@/lib/labelColors';
import { cn } from '@/lib/utils';
import type { TagOption } from '../types';

const model = defineModel<number[]>({ required: true });

const props = withDefaults(
    defineProps<{ options: TagOption[]; disabled?: boolean; placeholder?: string }>(),
    { disabled: false, placeholder: 'Selecione tags' },
);

const open = ref(false);
const search = ref('');

const selectedTags = computed(() =>
    props.options.filter((option) => model.value.includes(option.id)),
);

const filteredOptions = computed(() => {
    const term = search.value.trim().toLowerCase();

    if (term === '') {
        return props.options;
    }

    return props.options.filter((option) => option.name.toLowerCase().includes(term));
});

function toggle(id: number): void {
    model.value = model.value.includes(id)
        ? model.value.filter((value) => value !== id)
        : [...model.value, id];
}

function remove(id: number): void {
    model.value = model.value.filter((value) => value !== id);
}
</script>

<template>
    <div
        v-if="disabled"
        class="flex min-h-9 w-full flex-wrap items-center gap-1.5 rounded-md border border-input bg-transparent px-2 py-1.5 text-sm opacity-50"
    >
        <span class="px-1 text-muted-foreground">{{ placeholder }}</span>
    </div>
    <Popover v-else v-model:open="open">
        <PopoverTrigger as-child>
            <div
                role="button"
                tabindex="0"
                class="flex min-h-9 w-full flex-wrap items-center gap-1.5 rounded-md border border-input bg-transparent px-2 py-1.5 text-sm shadow-xs transition-[color,box-shadow] focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
            >
                <span
                    v-for="tag in selectedTags"
                    :key="tag.id"
                    class="flex items-center gap-1.5 rounded-full border px-2 py-0.5 text-xs"
                >
                    <span
                        class="size-2 rounded-full"
                        :style="{ backgroundColor: colorHex(tag.color) }"
                    />
                    {{ tag.name }}
                    <button
                        type="button"
                        class="text-muted-foreground hover:text-foreground"
                        :aria-label="`Remover ${tag.name}`"
                        @click.stop="remove(tag.id)"
                    >
                        <XIcon class="size-3" />
                    </button>
                </span>
                <span v-if="selectedTags.length === 0" class="px-1 text-muted-foreground">
                    {{ placeholder }}
                </span>
            </div>
        </PopoverTrigger>
        <PopoverContent class="w-(--reka-popover-trigger-width) p-0" align="start">
            <div class="border-b p-2">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Buscar tag..."
                    class="w-full bg-transparent px-1 text-sm outline-none placeholder:text-muted-foreground"
                />
            </div>
            <div class="max-h-56 overflow-y-auto p-1">
                <button
                    v-for="option in filteredOptions"
                    :key="option.id"
                    type="button"
                    :class="
                        cn(
                            'flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm transition hover:bg-accent',
                            model.includes(option.id) && 'font-medium',
                        )
                    "
                    @click="toggle(option.id)"
                >
                    <CheckIcon
                        :class="cn('size-4 shrink-0', model.includes(option.id) ? '' : 'opacity-0')"
                    />
                    <span
                        class="size-2.5 rounded-full"
                        :style="{ backgroundColor: colorHex(option.color) }"
                    />
                    {{ option.name }}
                </button>
                <p
                    v-if="filteredOptions.length === 0"
                    class="px-2 py-3 text-center text-sm text-muted-foreground"
                >
                    Nenhuma tag encontrada.
                </p>
            </div>
        </PopoverContent>
    </Popover>
</template>
