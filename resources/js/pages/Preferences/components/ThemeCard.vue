<script setup lang="ts">
import { CheckIcon } from '@lucide/vue';
import { computed } from 'vue';
import type { ThemeValue } from '@/composables/useAppearance';
import { cn } from '@/lib/utils';

/**
 * A theme offered as a miniature of what it looks like rather than as a word.
 * "System" is drawn split down the middle, which is the only honest way to show
 * a choice whose answer depends on the device.
 */
const props = defineProps<{ value: ThemeValue; label: string; selected: boolean }>();

defineEmits<{ select: [value: ThemeValue] }>();

const panes = computed<Array<'light' | 'dark'>>(() =>
    props.value === 'system' ? ['light', 'dark'] : [props.value],
);

const lineWidths = ['w-full', 'w-4/5', 'w-3/5'];
</script>

<template>
    <button
        type="button"
        class="group flex cursor-pointer flex-col items-center gap-2"
        :aria-pressed="selected"
        :aria-label="label"
        @click="$emit('select', value)"
    >
        <div
            :class="
                cn(
                    'relative h-24 w-36 overflow-hidden rounded-lg border-2 transition-colors',
                    selected
                        ? 'border-primary'
                        : 'border-border group-hover:border-zinc-300 dark:group-hover:border-zinc-600',
                )
            "
        >
            <div
                v-if="selected"
                class="absolute top-1 right-1 z-10 flex size-4 items-center justify-center rounded-full bg-primary"
            >
                <CheckIcon class="size-2.5 text-primary-foreground" aria-hidden="true" />
            </div>

            <div class="flex h-full">
                <!--
                    The app as it is actually laid out: header with the mark on the
                    left, the menu centred and the avatar on the right, then a
                    centred card on the page beneath it. A window that looked like
                    some other product would be worse than no preview at all.
                -->
                <div
                    v-for="pane in panes"
                    :key="pane"
                    :class="
                        cn(
                            'flex flex-col',
                            panes.length > 1 ? 'w-1/2' : 'w-full',
                            pane === 'light' ? 'bg-zinc-50' : 'bg-zinc-900',
                        )
                    "
                >
                    <div
                        :class="
                            cn(
                                'flex h-4 shrink-0 items-center justify-between border-b px-1.5',
                                pane === 'light'
                                    ? 'border-zinc-200 bg-white'
                                    : 'border-zinc-800 bg-zinc-950',
                            )
                        "
                    >
                        <span
                            :class="
                                cn(
                                    'h-1 w-3 rounded-full',
                                    pane === 'light' ? 'bg-zinc-400' : 'bg-zinc-600',
                                )
                            "
                        />
                        <div class="flex gap-1">
                            <span
                                v-for="item in 3"
                                :key="item"
                                :class="
                                    cn(
                                        'h-1 w-2 rounded-full',
                                        pane === 'light' ? 'bg-zinc-300' : 'bg-zinc-700',
                                    )
                                "
                            />
                        </div>
                        <span
                            :class="
                                cn(
                                    'size-1.5 rounded-full',
                                    pane === 'light' ? 'bg-zinc-400' : 'bg-zinc-600',
                                )
                            "
                        />
                    </div>

                    <div class="flex flex-1 items-center justify-center p-1.5">
                        <div
                            :class="
                                cn(
                                    'w-full space-y-1 rounded border p-1.5',
                                    pane === 'light'
                                        ? 'border-zinc-200 bg-white'
                                        : 'border-zinc-800 bg-zinc-950',
                                )
                            "
                        >
                            <span
                                v-for="line in lineWidths.length"
                                :key="line"
                                :class="
                                    cn(
                                        'block h-1 rounded-sm',
                                        lineWidths[line - 1],
                                        pane === 'light' ? 'bg-zinc-200' : 'bg-zinc-800',
                                    )
                                "
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <span
            :class="
                cn(
                    'text-xs font-medium',
                    selected ? 'text-zinc-700 dark:text-zinc-300' : 'text-zinc-400',
                )
            "
        >
            {{ label }}
        </span>
    </button>
</template>
