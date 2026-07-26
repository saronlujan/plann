<script setup lang="ts">
import type { ToasterProps } from 'vue-sonner';
import { reactiveOmit } from '@vueuse/core';
import { XIcon } from '@lucide/vue';
import { Toaster as Sonner } from 'vue-sonner';

const props = defineProps<ToasterProps>();
const delegatedProps = reactiveOmit(props, 'class', 'toastOptions');
</script>

<template>
    <Sonner
        class="toaster group"
        :style="{
            '--normal-bg': 'var(--popover)',
            '--normal-text': 'var(--primary)',
            '--normal-border': 'var(--border)',
            '--border-radius': 'var(--radius)',
            '--gray2': 'hsl(var(--popover) / 0.9)',
            '--gray3': 'var(--border)',
            '--gray4': 'var(--border)',
            '--gray5': 'var(--border)',
            '--gray12': 'var(--popover-foreground)',
        }"
        :toast-options="props.toastOptions ?? {
            classes: {
                toast: 'rounded-2xl',
            },
        }"
        v-bind="delegatedProps"
    >
        <template #close-icon>
            <XIcon class="size-4" />
        </template>
    </Sonner>
</template>
