<script setup lang="ts">
defineProps<{
    currencyCode: string;
    periodDisplay: string;
    actualIncome: string;
    actualExpense: string;
    actualTotal: string;
    expectedIncome: string;
    expectedExpense: string;
    expectedTotal: string;
}>();
</script>

<template>
    <section
        class="rounded-[28px] border border-white/10 bg-white/4 p-4 shadow-[0_24px_80px_rgba(0,0,0,0.35)] backdrop-blur-xl"
    >
        <div
            class="flex flex-col gap-3 border-b border-white/10 px-2 pb-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <p
                    class="text-xs font-semibold tracking-[0.28em] text-slate-400 uppercase"
                >
                    Cash flow snapshot
                </p>
                <h2 class="mt-1 text-lg font-semibold text-white">
                    {{ currencyCode }} overview
                </h2>
            </div>

            <div
                class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-slate-300"
            >
                {{ periodDisplay }}
            </div>
        </div>

        <div
            class="mt-4 overflow-hidden rounded-[22px] border border-white/10 bg-black/20"
        >
            <div class="divide-y divide-white/10">
                <div
                    class="grid grid-cols-[1fr_auto] items-center gap-4 px-5 py-4 sm:px-6"
                >
                    <div>
                        <p class="text-sm font-medium text-slate-300">Income</p>
                        <p class="mt-1 text-xs text-slate-500">
                            Realized movements only
                        </p>
                    </div>
                    <p class="text-sm font-semibold text-white">
                        {{ actualIncome }}
                    </p>
                </div>

                <div
                    class="grid grid-cols-[1fr_auto] items-center gap-4 px-5 py-4 sm:px-6"
                >
                    <div>
                        <p class="text-sm font-medium text-slate-300">
                            Expenses
                        </p>
                        <p class="mt-1 text-xs text-slate-500">
                            Realized movements only
                        </p>
                    </div>
                    <p class="text-sm font-semibold text-white">
                        {{ actualExpense }}
                    </p>
                </div>

                <div
                    class="grid grid-cols-[1fr_auto] items-center gap-4 px-5 py-4 sm:px-6"
                >
                    <div>
                        <p class="text-sm font-medium text-slate-300">Total</p>
                        <p class="mt-1 text-xs text-slate-500">
                            Income minus expenses
                        </p>
                    </div>
                    <p class="text-sm font-semibold text-emerald-300">
                        {{ actualTotal }}
                    </p>
                </div>

                <div
                    class="grid grid-cols-[1fr_auto] items-center gap-4 px-5 py-4 sm:px-6"
                >
                    <div>
                        <p class="text-sm font-medium text-slate-300">
                            Expected Income
                        </p>
                        <p class="mt-1 text-xs text-slate-500">
                            Includes scheduled income rows
                        </p>
                    </div>
                    <p class="text-sm font-semibold text-white">
                        {{ expectedIncome }}
                    </p>
                </div>

                <div
                    class="grid grid-cols-[1fr_auto] items-center gap-4 px-5 py-4 sm:px-6"
                >
                    <div>
                        <p class="text-sm font-medium text-slate-300">
                            Expected Expense
                        </p>
                        <p class="mt-1 text-xs text-slate-500">
                            Includes scheduled expense rows
                        </p>
                    </div>
                    <p class="text-sm font-semibold text-white">
                        {{ expectedExpense }}
                    </p>
                </div>

                <div
                    class="grid grid-cols-[1fr_auto] items-center gap-4 bg-white/5 px-5 py-4 sm:px-6"
                >
                    <div>
                        <p class="text-sm font-semibold text-white">
                            Expected Total
                        </p>
                        <p class="mt-1 text-xs text-slate-400">
                            Projected balance for the selected period
                        </p>
                    </div>
                    <p class="text-base font-semibold text-emerald-300">
                        {{ expectedTotal }}
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>
