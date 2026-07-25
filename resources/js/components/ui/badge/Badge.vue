<script setup lang="ts">
import type { PrimitiveProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { Primitive } from 'reka-ui';
import { cn } from '@/lib/utils';
import { type BadgeVariants, badgeVariants } from '.';

const props = withDefaults(
    defineProps<PrimitiveProps & { variant?: BadgeVariants['variant']; class?: HTMLAttributes['class'] }>(),
    {
        as: 'span',
    },
);

const delegatedProps = reactiveOmit(props, 'class', 'variant');
</script>

<template>
    <Primitive
        data-slot="badge"
        v-bind="delegatedProps"
        :class="cn(badgeVariants({ variant }), props.class)"
    >
        <slot />
    </Primitive>
</template>
