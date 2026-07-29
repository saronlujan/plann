<script setup lang="ts">
import LocaleSelector from '@/components/LocaleSelector.vue';
</script>

<template>
    <main class="relative min-h-screen bg-white px-4 py-12 text-zinc-950">
        <div class="absolute top-4 right-4 z-10">
            <LocaleSelector />
        </div>

        <div
            class="mx-auto flex min-h-[calc(100vh-6rem)] w-full max-w-md flex-col justify-center gap-8"
        >
            <!--
                The mark belongs to every screen in front of the sign-in wall, not
                to one of them, so it sits in the layout. No dark:invert here: this
                surface is white in both themes.
            -->
            <span class="flex items-center gap-2 self-start">
                <img src="/img/logo.png" alt="Mercante" class="h-7 w-auto" />
                <span
                    class="rounded-full bg-primary px-2 py-0.5 text-xs leading-none font-bold tracking-wide text-white"
                >
                    money
                </span>
            </span>

            <slot />
        </div>
    </main>
</template>
