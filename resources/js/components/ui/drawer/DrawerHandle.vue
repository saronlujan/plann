<script setup lang="ts">
import { DrawerHandle } from 'vaul-vue';
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

/**
 * The grip that drags the drawer.
 *
 * A styled div is enough to *look* like a handle, but `handleOnly` on the root
 * only recognises this component — without it, restricting the drag would leave
 * nothing able to start one.
 */
const props = defineProps<{ class?: HTMLAttributes['class'] }>();
</script>

<template>
    <DrawerHandle
        data-slot="drawer-handle"
        :class="
            cn(
                'mx-auto mt-4 h-2 w-25 shrink-0 cursor-grab rounded-full bg-muted active:cursor-grabbing',
                props.class,
            )
        "
    />
</template>
