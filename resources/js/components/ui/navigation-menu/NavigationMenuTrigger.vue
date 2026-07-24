<script setup lang="ts">
import { ChevronDownIcon } from '@lucide/vue';
import { reactiveOmit } from '@vueuse/core';
import {
    NavigationMenuTrigger as NavigationMenuTriggerPrimitive,
    useForwardProps,
} from 'reka-ui';
import type { NavigationMenuTriggerProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
import { navigationMenuTriggerStyle } from '.';

const props = defineProps<NavigationMenuTriggerProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = reactiveOmit(props, 'class');
const forwardedProps = useForwardProps(delegatedProps);
</script>

<template>
    <NavigationMenuTriggerPrimitive
        data-slot="navigation-menu-trigger"
        v-bind="forwardedProps"
        :class="cn(navigationMenuTriggerStyle(), 'group', props.class)"
    >
        <slot />
        <ChevronDownIcon
            class="relative top-px ml-1 size-3 transition duration-200 group-data-[state=open]:rotate-180"
            aria-hidden="true"
        />
    </NavigationMenuTriggerPrimitive>
</template>
