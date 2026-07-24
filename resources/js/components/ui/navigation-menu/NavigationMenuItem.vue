<script setup lang="ts">
import { reactiveOmit } from '@vueuse/core';
import { NavigationMenuItem as NavigationMenuItemPrimitive } from 'reka-ui';
import type { NavigationMenuItemProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<NavigationMenuItemProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = reactiveOmit(props, 'class');
</script>

<template>
    <NavigationMenuItemPrimitive
        data-slot="navigation-menu-item"
        v-bind="delegatedProps"
        :class="cn('relative', props.class)"
    >
        <slot />
    </NavigationMenuItemPrimitive>
</template>
