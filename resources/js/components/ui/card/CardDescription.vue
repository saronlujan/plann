<script setup lang="ts">
import type { PrimitiveProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { Primitive } from 'reka-ui';
import { cn } from '@/lib/utils';

interface Props extends PrimitiveProps {
    class?: HTMLAttributes['class'];
}

const props = withDefaults(defineProps<Props>(), {
    as: 'p',
});
</script>

<template>
    <Primitive
        data-slot="card-description"
        :as="as"
        :as-child="asChild"
        :class="cn('text-sm text-zinc-400', props.class)"
    >
        <slot />
    </Primitive>
</template>
