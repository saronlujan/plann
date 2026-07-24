<script setup lang="ts">
import type { TransactionEntry } from '../types';

defineProps<{
    entries: TransactionEntry[];
    periodDisplay: string;
    adjustmentsCount: number;
}>();

defineEmits<{
    edit: [entry: TransactionEntry];
    pay: [transactionId: number];
}>();
</script>

<template>
    <section
        class="rounded-[28px] border border-white/10 bg-white/4 p-4 shadow-[0_24px_80px_rgba(0,0,0,0.35)] backdrop-blur-xl"
    >
        <div
            class="flex items-center justify-between gap-3 border-b border-white/10 px-2 pb-4"
        >
            <div>
                <p
                    class="text-xs font-semibold tracking-[0.28em] text-slate-400 uppercase"
                >
                    Transactions
                </p>
                <h2 class="mt-1 text-lg font-semibold text-white">
                    Virtual list for {{ periodDisplay }}
                </h2>
            </div>

            <div
                class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-slate-300"
            >
                {{ adjustmentsCount }} adjustment(s)
            </div>
        </div>

        <div
            v-if="entries.length === 0"
            class="p-8 text-center text-sm text-slate-400"
        >
            No transactions found for this period.
        </div>

        <div
            v-else
            class="mt-4 overflow-hidden rounded-[22px] border border-white/10"
        >
            <div
                class="grid grid-cols-12 gap-4 border-b border-white/10 bg-black/30 px-4 py-3 text-[11px] font-semibold tracking-[0.24em] text-slate-400 uppercase"
            >
                <div class="col-span-3">Type</div>
                <div class="col-span-4">Description</div>
                <div class="col-span-2">Amount</div>
                <div class="col-span-2">Date</div>
                <div class="col-span-1 text-right">Actions</div>
            </div>

            <div
                v-for="entry in entries"
                :key="entry.id"
                class="grid grid-cols-12 gap-4 border-b border-white/5 bg-white/3 px-4 py-4 text-sm last:border-b-0"
            >
                <div class="col-span-3 flex items-start gap-3">
                    <span
                        class="mt-0.5 inline-flex rounded-full border border-white/10 px-3 py-1 text-xs font-medium tracking-wide text-slate-200 uppercase"
                    >
                        {{
                            entry.movement_type === 'income'
                                ? 'Income'
                                : 'Expense'
                        }}
                    </span>
                </div>

                <div class="col-span-4 min-w-0">
                    <p class="truncate font-semibold text-white">
                        {{ entry.label }}
                    </p>
                    <p class="mt-1 truncate text-xs text-slate-400">
                        {{ entry.source }} · {{ entry.currency_code }} ·
                        {{ entry.schedule_type }}
                    </p>
                </div>

                <div class="col-span-2 font-semibold text-white">
                    {{ entry.currency_symbol }}{{ entry.amount }}
                </div>

                <div class="col-span-2 text-slate-300">
                    {{ entry.date }}
                </div>

                <div class="col-span-1 flex justify-end gap-2 text-slate-200">
                    <button
                        type="button"
                        class="grid h-9 min-w-18 place-items-center rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-3 text-xs font-semibold text-emerald-200 transition hover:bg-emerald-500/20 disabled:cursor-not-allowed disabled:border-white/10 disabled:bg-white/5 disabled:text-slate-500"
                        :disabled="entry.paid_at !== null"
                        @click="
                            entry.paid_at === null &&
                            $emit('pay', entry.transaction_id)
                        "
                    >
                        {{ entry.paid_at ? 'Pago' : 'Pagar' }}
                    </button>
                    <button
                        type="button"
                        class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/5 transition hover:bg-white/10"
                        @click="$emit('edit', entry)"
                    >
                        ✎
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>
