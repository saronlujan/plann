<script setup lang="ts">
defineProps<{
    onAddTransaction: () => void;
}>();
</script>

<template>
    <header
        class="flex flex-col gap-4 rounded-[28px] border border-white/10 bg-white/5 px-5 py-4 shadow-[0_24px_80px_rgba(0,0,0,0.35)] backdrop-blur-xl lg:flex-row lg:items-center lg:justify-between"
    >
        <div class="flex items-center gap-4">
            <div
                class="flex h-11 w-11 items-center justify-center rounded-2xl border border-white/10 bg-black/40 text-sm font-semibold text-white shadow-inner shadow-black/30"
            >
                LM
            </div>

            <div>
                <p class="text-lg font-semibold tracking-wide text-white">
                    Laramoney 1.0
                </p>
                <p class="text-xs text-slate-400">Build without js</p>
            </div>
        </div>

        <nav class="flex flex-wrap items-center gap-2 text-sm text-slate-300">
            <a
                href="/transactions"
                class="rounded-full border border-white/10 bg-white/10 px-4 py-2 text-white"
            >
                Transactions
            </a>
            <a
                href="#"
                class="rounded-full px-4 py-2 transition hover:bg-white/5"
            >
                Categories
            </a>
            <a
                href="#"
                class="rounded-full px-4 py-2 transition hover:bg-white/5"
            >
                Contacts
            </a>
        </nav>

        <div class="flex items-center gap-2">
            <button
                class="grid h-10 w-10 place-items-center rounded-full border border-white/10 bg-white/5 text-slate-300 transition hover:bg-white/10"
                type="button"
            >
                ◌
            </button>
            <button
                class="grid h-10 w-10 place-items-center rounded-full border border-white/10 bg-white/5 text-slate-300 transition hover:bg-white/10"
                type="button"
            >
                ◐
            </button>
            <button
                class="grid h-10 w-10 place-items-center rounded-full border border-white/10 bg-white/5 text-slate-300 transition hover:bg-white/10"
                type="button"
            >
                ▭
            </button>
            <button
                class="grid h-10 w-10 place-items-center rounded-full border border-white/10 bg-white/5 text-slate-300 transition hover:bg-white/10"
                type="button"
            >
                ↗
            </button>
            <div
                class="grid h-10 w-10 place-items-center rounded-full bg-white text-sm font-semibold text-slate-900"
            >
                SL
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <div
                class="rounded-full border border-red-500/80 bg-red-500/10 px-4 py-2 text-sm text-red-200"
            >
                Overdue
                <span
                    class="ml-2 inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-red-500 px-2 text-xs font-semibold text-white"
                >
                    0
                </span>
            </div>

            <button
                type="button"
                class="rounded-full border border-white/10 bg-white/5 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-white/10"
                @click="onAddTransaction"
            >
                Add Transaction
            </button>
            <button
                type="button"
                class="grid h-11 w-11 place-items-center rounded-full border border-white/10 bg-white/5 text-slate-200 transition hover:bg-white/10"
            >
                ↗
            </button>
        </div>
    </header>
</template>
