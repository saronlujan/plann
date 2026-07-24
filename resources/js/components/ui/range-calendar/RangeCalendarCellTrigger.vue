<script lang="ts" setup>
import type { RangeCalendarCellTriggerProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { RangeCalendarCellTrigger, useForwardProps } from 'reka-ui';
import { cn } from '@/lib/utils';
import { buttonVariants } from '@/components/ui/button';

const props = defineProps<RangeCalendarCellTriggerProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = reactiveOmit(props, 'class');

const forwardedProps = useForwardProps(delegatedProps);
</script>

<template>
    <RangeCalendarCellTrigger
        data-slot="range-calendar-cell-trigger"
        :class="cn(
            buttonVariants({ variant: 'ghost' }),
            'size-8 p-0 font-normal aria-selected:opacity-100',
            props.class,
        )"
        v-bind="forwardedProps"
    >
        <slot />
    </RangeCalendarCellTrigger>
</template>
