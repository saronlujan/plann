<script setup lang="ts">
/**
 * The title block every page opens with, and whatever action belongs beside it.
 *
 * The wrapper is the same whether or not anything fills the slot: with a single
 * child, `justify-between` puts it exactly where a plain column would.
 */
defineProps<{ title: string; subtitle?: string }>();
</script>

<template>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-col">
            <h1 class="text-lg font-semibold md:text-xl">{{ title }}</h1>
            <span v-if="subtitle" class="text-sm text-muted-foreground">{{ subtitle }}</span>
        </div>
        <slot />
    </div>
</template>
