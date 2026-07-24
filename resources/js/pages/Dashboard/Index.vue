<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import DefaultLayout from '@/layouts/DefaultLayout.vue';

const page = usePage<{ auth: { user: { name: string } } }>();
</script>

<template>
    <Head title="Dashboard" />
    <DefaultLayout>
        <section class="space-y-10">
            <header class="space-y-3">
                <div>
                    <h1 class="font-medium tracking-tight text-zinc-950">
                        {{ $t('dashboard.welcome', { name: page.props.auth.user.name }) }}
                    </h1>
                </div>
            </header>
        </section>
    </DefaultLayout>
</template>
