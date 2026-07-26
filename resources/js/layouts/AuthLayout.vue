<script setup lang="ts">
import LocaleSelector from '@/components/LocaleSelector.vue';
</script>

<template>
    <main class="relative min-h-screen bg-white px-4 py-12 text-zinc-950">
        <div class="absolute top-4 right-4 z-10">
            <LocaleSelector />
        </div>

        <div
            class="mx-auto flex min-h-[calc(100vh-6rem)] w-full max-w-md items-center justify-center"
        >
            <slot />
        </div>
    </main>
</template>
