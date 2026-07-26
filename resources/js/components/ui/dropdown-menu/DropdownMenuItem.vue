<script setup lang="ts">
import { reactiveOmit } from '@vueuse/core';
import { DropdownMenuItem, useForwardPropsEmits } from 'reka-ui';
import type { DropdownMenuItemEmits, DropdownMenuItemProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

defineOptions({
    inheritAttrs: false,
});

const props = defineProps<DropdownMenuItemProps & { class?: HTMLAttributes['class'] }>();
const emits = defineEmits<DropdownMenuItemEmits>();

const delegatedProps = reactiveOmit(props, 'class');
const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
    <DropdownMenuItem
        data-slot="dropdown-menu-item"
        v-bind="{ ...$attrs, ...forwarded }"
        :class="cn(
            'relative flex cursor-default select-none items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-hidden transition-colors focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50',
            props.class,
        )"
    >
        <slot />
    </DropdownMenuItem>
</template>
