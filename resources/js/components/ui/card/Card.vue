<script setup lang="ts">
import type { PrimitiveProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { Primitive } from 'reka-ui';
import { cn } from '@/lib/utils';

interface Props extends PrimitiveProps {
    class?: HTMLAttributes['class'];
}

const props = withDefaults(defineProps<Props>(), {
    as: 'div',
});
</script>

<template>
    <Primitive
        data-slot="card"
        :as="as"
        :as-child="asChild"
        :class="cn('flex flex-col gap-5 rounded-xl border bg-card p-3 text-card-foreground md:p-5', props.class)"
    >
        <slot />
    </Primitive>
</template>
