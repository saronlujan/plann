<script setup lang="ts">
import { reactiveOmit } from '@vueuse/core';
import { DropdownMenuLabel, useForwardProps } from 'reka-ui';
import type { DropdownMenuLabelProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

defineOptions({
    inheritAttrs: false,
});

const props = defineProps<DropdownMenuLabelProps & { class?: HTMLAttributes['class'] }>();
const delegatedProps = reactiveOmit(props, 'class');
const forwarded = useForwardProps(delegatedProps);
</script>

<template>
    <DropdownMenuLabel
        data-slot="dropdown-menu-label"
        v-bind="{ ...$attrs, ...forwarded }"
        :class="cn('px-2 py-1.5 text-sm font-semibold text-foreground', props.class)"
    >
        <slot />
    </DropdownMenuLabel>
</template>
