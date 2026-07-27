<script setup lang="ts">
import { reactiveOmit } from '@vueuse/core';
import type { SwitchRootEmits, SwitchRootProps } from 'reka-ui';
import { SwitchRoot, SwitchThumb, useForwardPropsEmits } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
import { type SwitchVariants, switchThumbVariants, switchVariants } from '.';

const props = withDefaults(
    defineProps<
        SwitchRootProps & { class?: HTMLAttributes['class']; size?: SwitchVariants['size'] }
    >(),
    {
        size: 'default',
    },
);
const emits = defineEmits<SwitchRootEmits>();

const delegatedProps = reactiveOmit(props, 'class', 'size');

const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
    <SwitchRoot
        data-slot="switch"
        v-bind="forwarded"
        :class="cn(switchVariants({ size: props.size }), props.class)"
    >
        <SwitchThumb
            data-slot="switch-thumb"
            :class="cn(switchThumbVariants({ size: props.size }))"
        />
    </SwitchRoot>
</template>
