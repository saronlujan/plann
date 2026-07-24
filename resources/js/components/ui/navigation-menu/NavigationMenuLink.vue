<script setup lang="ts">
import { reactiveOmit } from '@vueuse/core';
import {
    NavigationMenuLink as NavigationMenuLinkPrimitive,
    useForwardPropsEmits,
} from 'reka-ui';
import type { NavigationMenuLinkEmits, NavigationMenuLinkProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<NavigationMenuLinkProps & { class?: HTMLAttributes['class'] }>();
const emits = defineEmits<NavigationMenuLinkEmits>();

const delegatedProps = reactiveOmit(props, 'class');
const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
    <NavigationMenuLinkPrimitive
        data-slot="navigation-menu-link"
        v-bind="forwarded"
        :class="cn('data-[active=true]:focus:bg-muted data-[active=true]:hover:bg-muted data-[active=true]:bg-muted/50 focus-visible:ring-ring/50 hover:bg-muted focus:bg-muted flex items-center gap-2 rounded-md p-2 text-sm transition-all outline-none focus-visible:ring-1 focus-visible:outline-1 in-data-[slot=navigation-menu-content]:rounded-md [&_svg:not([class*=size-])]:size-4', props.class)"
    >
        <slot />
    </NavigationMenuLinkPrimitive>
</template>
