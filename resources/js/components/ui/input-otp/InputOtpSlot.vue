<script setup lang="ts">
import type { PinInputInputProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { PinInputInput, useForwardProps } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps<PinInputInputProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = reactiveOmit(props, 'class');
const forwardedProps = useForwardProps(delegatedProps);
</script>

<template>
    <PinInputInput
        data-slot="input-otp-slot"
        v-bind="forwardedProps"
        :class="
            cn(
                'border-input relative flex size-10 items-center justify-center rounded-md border bg-transparent text-center text-sm shadow-xs transition-all outline-none focus:z-10 focus:border-ring focus:ring-[3px] focus:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50',
                props.class,
            )
        "
    />
</template>
