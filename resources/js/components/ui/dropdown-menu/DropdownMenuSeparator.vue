<script setup lang="ts">
import { reactiveOmit } from '@vueuse/core';
import { DropdownMenuSeparator, useForwardProps } from 'reka-ui';
import type { DropdownMenuSeparatorProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

defineOptions({
    inheritAttrs: false,
});

const props = defineProps<DropdownMenuSeparatorProps & { class?: HTMLAttributes['class'] }>();
const delegatedProps = reactiveOmit(props, 'class');
const forwarded = useForwardProps(delegatedProps);
</script>

<template>
    <DropdownMenuSeparator
        data-slot="dropdown-menu-separator"
        v-bind="{ ...$attrs, ...forwarded }"
        :class="cn('-mx-1 my-1 h-px bg-zinc-200', props.class)"
    />
</template>
