<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Card from '@/components/ui/card/Card.vue';
import DefaultLayout from '@/layouts/DefaultLayout.vue';
</script>

<template>
    <Head title="Contacts" />

    <DefaultLayout>
        <main class="flex flex-col p-3 md:p-5">
            <div class="mt-2 mb-5 flex w-full md:mt-0">
                <div class="flex flex-1 items-center justify-between gap-5">
                    <div class="flex items-start gap-2">
                        <div class="flex flex-col">
                            <h1 class="text-lg font-semibold md:text-xl">Your Contacts</h1>
                            <span class="hidden text-sm text-zinc-400 md:block dark:text-zinc-500">
                                You can add, edit, and delete contacts as needed.
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <Card></Card>
        </main>
    </DefaultLayout>
</template>
