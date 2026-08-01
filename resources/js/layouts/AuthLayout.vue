<script setup lang="ts">
import LocaleSelector from '@/components/LocaleSelector.vue';
</script>

<template>
    <main class="relative min-h-screen bg-white px-4 py-12 text-zinc-950">
        <div class="absolute right-4 top-4 z-10">
            <LocaleSelector />
        </div>

        <div
            class="mx-auto flex min-h-[calc(100vh-6rem)] w-full max-w-md flex-col justify-center gap-8"
        >
            <span class="flex items-center gap-2 self-start">
                <img src="/img/logo.png" alt="Mercante" class="h-12 w-auto" />
            </span>
            <slot />
        </div>
    </main>
</template>
