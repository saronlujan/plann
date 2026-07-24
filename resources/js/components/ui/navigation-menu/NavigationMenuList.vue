<script setup lang="ts">
import { reactiveOmit } from '@vueuse/core';
import { NavigationMenuList as NavigationMenuListPrimitive, useForwardProps } from 'reka-ui';
import type { NavigationMenuListProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<NavigationMenuListProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = reactiveOmit(props, 'class');
const forwardedProps = useForwardProps(delegatedProps);
</script>

<template>
    <NavigationMenuListPrimitive
        data-slot="navigation-menu-list"
        v-bind="forwardedProps"
        :class="cn('group flex flex-1 list-none items-center justify-center gap-0', props.class)"
    >
        <slot />
    </NavigationMenuListPrimitive>
</template>
