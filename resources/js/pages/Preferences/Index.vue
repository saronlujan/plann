<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import DefaultLayout from '@/layouts/DefaultLayout.vue';
</script>

<template>
    <Head :title="trans('ui.preferences.page_title')" />
    <DefaultLayout> </DefaultLayout>
</template>
