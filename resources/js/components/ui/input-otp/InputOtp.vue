<script setup lang="ts">
import type { PinInputRootEmits, PinInputRootProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { PinInputRoot, useForwardPropsEmits } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps<PinInputRootProps & { class?: HTMLAttributes['class'] }>();
const emits = defineEmits<PinInputRootEmits>();

const delegatedProps = reactiveOmit(props, 'class');
const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
    <PinInputRoot
        data-slot="input-otp"
        v-bind="forwarded"
        :class="cn('flex items-center gap-2 has-disabled:opacity-50', props.class)"
    >
        <slot />
    </PinInputRoot>
</template>
