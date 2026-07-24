<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import type { FilterOption, TransactionFilters } from '../types';

defineProps<{
    period: string;
    periodDisplay: string;
    periodPrevious: string;
    periodNext: string;
    filters: TransactionFilters;
    kindOptions: FilterOption[];
}>();
</script>

<template>
    <section class="rounded-[28px] border border-white/10 bg-white/4 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.35)] backdrop-blur-xl">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-4xl font-semibold tracking-tight text-white">
                    Transactions
                </p>
                <p class="mt-2 text-sm text-slate-400">
                    Manage your income and expenses.
                </p>
            </div>
        </div>

        <Form action="/transactions" method="get" class="mt-6 space-y-4">
            <input type="hidden" name="period" :value="period" />

            <div class="grid gap-4 rounded-[22px] border border-white/10 bg-black/20 p-4 lg:grid-cols-[1.2fr_1fr_1fr]">
                <div class="space-y-3">
                    <p class="text-xs font-semibold tracking-[0.28em] text-slate-400 uppercase">
                        Quick Month
                    </p>
                    <div class="flex items-center gap-2">
                        <Link :href="periodPrevious" class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/5 text-slate-200 transition hover:bg-white/10">‹</Link>
                        <div class="min-w-37.5 rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-center text-sm font-medium text-white">
                            {{ periodDisplay }}
                        </div>
                        <Link :href="periodNext" class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/5 text-slate-200 transition hover:bg-white/10">›</Link>
                    </div>
                </div>

                <label class="space-y-2">
                    <p class="text-xs font-semibold tracking-[0.28em] text-slate-400 uppercase">Date From</p>
                    <div class="flex items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-3 py-2.5">
                        <input type="date" name="date_from" :value="filters.date_from" class="w-full bg-transparent text-sm text-white outline-none placeholder:text-slate-500" />
                    </div>
                </label>

                <label class="space-y-2">
                    <p class="text-xs font-semibold tracking-[0.28em] text-slate-400 uppercase">Date To</p>
                    <div class="flex items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-3 py-2.5">
                        <input type="date" name="date_to" :value="filters.date_to" class="w-full bg-transparent text-sm text-white outline-none placeholder:text-slate-500" />
                    </div>
                </label>

                <div class="flex items-end justify-end lg:col-span-3">
                    <button type="submit" class="rounded-xl border border-white/10 bg-white/10 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-white/15">
                        Apply
                    </button>
                </div>
            </div>

            <div class="grid gap-4 rounded-[22px] border border-white/10 bg-black/20 p-4 lg:grid-cols-[1.5fr_auto_220px_220px] lg:items-end">
                <label class="space-y-2">
                    <p class="text-xs font-semibold tracking-[0.28em] text-slate-400 uppercase">Search by name</p>
                    <div class="flex items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-3 py-2.5">
                        <input type="text" name="search" :value="filters.search" placeholder="Type a transaction name" class="w-full bg-transparent text-sm text-white outline-none placeholder:text-slate-500" />
                    </div>
                </label>

                <button type="submit" class="rounded-xl border border-white/10 bg-white/5 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-white/10">
                    Search
                </button>

                <label class="space-y-2">
                    <p class="text-xs font-semibold tracking-[0.28em] text-slate-400 uppercase">Filter</p>
                    <select name="kind" :value="filters.kind" class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2.5 text-sm text-white outline-none">
                        <option v-for="option in kindOptions" :key="option.value" :value="option.value" class="bg-slate-900">
                            {{ option.label }}
                        </option>
                    </select>
                </label>

                <label class="space-y-2">
                    <p class="text-xs font-semibold tracking-[0.28em] text-slate-400 uppercase">Order By</p>
                    <select name="order" :value="filters.order" class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2.5 text-sm text-white outline-none">
                        <option value="recent" class="bg-slate-900">Recent</option>
                        <option value="oldest" class="bg-slate-900">Oldest</option>
                    </select>
                </label>
            </div>
        </Form>
    </section>
</template>