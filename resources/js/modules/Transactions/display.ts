import { trans } from 'laravel-vue-i18n';
import type { TransactionEntry } from './types';

// Movement colors are financial data encoding (in = green, out = red),
// intentionally decoupled from the configurable brand primary.
export const movementBadgeClass: Record<string, string> = {
    expense:
        'rounded-full border-transparent bg-zinc-100 text-red-600 dark:bg-zinc-800 dark:text-red-400',
    income: 'rounded-full border-transparent bg-zinc-100 text-emerald-600 dark:bg-zinc-800 dark:text-emerald-400',
    transfer:
        'rounded-full border-transparent bg-zinc-100 text-sky-600 dark:bg-zinc-800 dark:text-sky-400',
};

// The visual kind: transfers are stored as an income/expense leg (so balances
// stay correct) but should read as "transfer" in the UI, not in/out.
export function movementKind(entry: TransactionEntry): string {
    return entry.is_transfer ? 'transfer' : entry.movement_type;
}

// Resolved at call time so it follows locale changes (the labels must not be
// frozen at module-import time).
export function movementLabel(type: string): string {
    return trans(`transactions.movement.${type}`);
}

export function signedAmount(entry: TransactionEntry): number {
    const value = Number.parseFloat(entry.amount);

    if (entry.movement_type === 'income' || entry.movement_type === 'transfer') {
        return value;
    }

    return -value;
}

export function amountClass(entry: TransactionEntry): string {
    if (entry.is_transfer) {
        return 'text-sky-600 dark:text-sky-400';
    }

    return entry.movement_type === 'expense'
        ? 'text-red-600 dark:text-red-400'
        : 'text-emerald-600 dark:text-emerald-400';
}

export function scheduleLabel(entry: TransactionEntry): string {
    if (entry.schedule_type === 'installment') {
        return trans('transactions.schedule.installment', {
            number: (entry.installment_number ?? 1).toString(),
            total: (entry.installments_total ?? 1).toString(),
        });
    }

    if (entry.schedule_type === 'recurring') {
        return trans('transactions.schedule.recurring');
    }

    return trans('transactions.schedule.unique');
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
