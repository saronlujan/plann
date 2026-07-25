<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Card from '@/components/ui/card/Card.vue';
import DefaultLayout from '@/layouts/DefaultLayout.vue';
import CornerUpLeft from '@/components/icons/CornerUpLeft.vue';
import { CornerUpLeftIcon } from 'lucide-vue-next';
</script>

<template>
    <Head title="Configurações" />

    <DefaultLayout>
        <main class="flex flex-col p-3 md:p-5">
            <div class="mt-2 mb-5 flex w-full md:mt-0">
                <div class="flex flex-1 items-center justify-between gap-5">
                    <div class="flex items-start gap-2">
                        <div class="flex flex-col">
                            <h1 class="text-lg font-semibold md:text-xl">Settings</h1>
                            <span class="hidden text-sm text-zinc-400 md:block dark:text-zinc-500">
                                Select currency, language, and other preferences for your account.
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <Card>
                <div
                    class="grid grid-cols-12 gap-5 border-b border-zinc-100 pb-5 dark:border-zinc-800"
                >
                    <div class="col-span-12 flex flex-col lg:col-span-4">
                        <h2 class="font-medium">title</h2>
                        <span class="text-xs font-medium text-zinc-400 dark:text-zinc-500"
                            >subtitle</span
                        >
                    </div>
                    <div class="col-span-12 lg:col-span-8">lorem ipsum</div>
                </div>
            </Card>
        </main>
    </DefaultLayout>
</template>
