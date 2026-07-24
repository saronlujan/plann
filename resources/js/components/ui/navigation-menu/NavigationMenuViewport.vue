<script setup lang="ts">
import { reactiveOmit } from '@vueuse/core';
import {
    NavigationMenuViewport as NavigationMenuViewportPrimitive,
    useForwardProps,
} from 'reka-ui';
import type { NavigationMenuViewportProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<NavigationMenuViewportProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = reactiveOmit(props, 'class');
const forwardedProps = useForwardProps(delegatedProps);
</script>

<template>
    <div class="absolute top-full left-0 isolate z-50 flex justify-center">
        <NavigationMenuViewportPrimitive
            data-slot="navigation-menu-viewport"
            v-bind="forwardedProps"
            :class="cn('origin-top-center relative mt-1.5 h-[var(--reka-navigation-menu-viewport-height)] w-full overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-lg data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-90 md:w-[var(--reka-navigation-menu-viewport-width)]', props.class)"
        />
    </div>
</template>
