import type { TransactionEntry } from './types';

// Movement colors are financial data encoding (in = green, out = red),
// intentionally decoupled from the configurable brand primary.
export const movementBadge: Record<string, { label: string; class: string }> = {
    expense: {
        label: 'Despesa',
        class: 'rounded-full border-transparent bg-zinc-100 text-red-600 dark:bg-zinc-800 dark:text-red-400',
    },
    income: {
        label: 'Receita',
        class: 'rounded-full border-transparent bg-zinc-100 text-emerald-600 dark:bg-zinc-800 dark:text-emerald-400',
    },
    transfer: {
        label: 'Transferência',
        class: 'rounded-full border-transparent bg-zinc-100 text-sky-600 dark:bg-zinc-800 dark:text-sky-400',
    },
};

export function signedAmount(entry: TransactionEntry): number {
    const value = Number.parseFloat(entry.amount);

    if (entry.movement_type === 'income' || entry.movement_type === 'transfer') {
        return value;
    }

    return -value;
}

export function amountClass(entry: TransactionEntry): string {
    return entry.movement_type === 'expense'
        ? 'text-red-600 dark:text-red-400'
        : 'text-emerald-600 dark:text-emerald-400';
}

export function scheduleLabel(entry: TransactionEntry): string {
    if (entry.schedule_type === 'installment') {
        return `Parcelado ${entry.installment_number ?? 1}/${entry.installments_total ?? 1}`;
    }

    if (entry.schedule_type === 'recurring') {
        return 'Recorrente';
    }

    return 'Único';
}

// Due state for unpaid entries: overdue (past date) or due within the next 7 days.
export function dueStatus(entry: TransactionEntry): 'overdue' | 'soon' | null {
    if (entry.paid_at) {
        return null;
    }

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const due = new Date(`${entry.date}T00:00:00`);
    const diffDays = Math.round((due.getTime() - today.getTime()) / 86_400_000);

    if (diffDays < 0) {
        return 'overdue';
    }

    if (diffDays <= 7) {
        return 'soon';
    }

    return null;
}
