<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import type { AvatarVariants } from '.';
import { AvatarRoot } from 'reka-ui';
import { cn } from '@/lib/utils';
import { avatarVariants } from '.';

const props = withDefaults(
    defineProps<{
        class?: HTMLAttributes['class'];
        size?: AvatarVariants['size'];
    }>(),
    {
        size: 'default',
    },
);
</script>

<template>
    <AvatarRoot
        data-slot="avatar"
        :data-size="props.size"
        :class="cn(avatarVariants({ size: props.size }), 'overflow-hidden', props.class)"
    >
        <slot />
    </AvatarRoot>
</template>
