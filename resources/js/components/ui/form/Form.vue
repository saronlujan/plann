<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <form
        v-bind="$attrs"
        :class="cn('flex flex-col gap-5', props.class)"
    >
        <slot />
    </form>
</template>
