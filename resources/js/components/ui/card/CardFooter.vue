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
        data-slot="card-footer"
        :as="as"
        :as-child="asChild"
        :class="cn('flex items-center justify-end gap-2 border-t border-zinc-100 p-4 dark:border-zinc-900', props.class)"
    >
        <slot />
    </Primitive>
</template>
