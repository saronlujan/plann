<script setup lang="ts">
import type { PrimitiveProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { Primitive } from 'reka-ui';
import { cn } from '@/lib/utils';

interface Props extends PrimitiveProps {
    class?: HTMLAttributes['class'];
}

const props = withDefaults(defineProps<Props>(), {
    as: 'h2',
});
</script>

<template>
    <Primitive
        data-slot="card-title"
        :as="as"
        :as-child="asChild"
        :class="cn('text-lg font-semibold', props.class)"
    >
        <slot />
    </Primitive>
</template>
