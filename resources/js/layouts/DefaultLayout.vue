<script setup lang="ts">
import AppHeader from '@/components/layout/AppHeader.vue';
</script>

<template>
    <div class="min-h-screen bg-zinc-50 text-foreground dark:bg-zinc-900">
        <AppHeader />
        <main class="mx-auto w-full max-w-7xl px-4 py-6 lg:px-6 xl:px-8">
            <slot />
        </main>
    </div>
</template>
