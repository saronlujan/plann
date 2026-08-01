<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { DownloadIcon } from '@lucide/vue';
import { computed } from 'vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import DatePicker from '@/components/ui/date-picker/DatePicker.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import DefaultLayout from '@/layouts/DefaultLayout.vue';
import { colorHex } from '@/lib/labelColors';
import { exportMethod as exportReport, index as reportsIndex } from '@/routes/reports';

type Option = { value: string; label: string };
type MonthlyRow = { month: string; label: string; income: string; expenses: string; net: string };
type CategoryRow = { type: string; name: string; color: string; total: string; share: string };
type AccountRow = { name: string; income: string; expenses: string; net: string };

const props = withDefaults(
    defineProps<{
        ready: boolean;
        filters?: { from: string; to: string; currency_id: number };
        currency?: { id: number; code: string; symbol: string };
        currencyOptions?: Option[];
        report?: {
            summary: Record<string, string>;
            monthly: MonthlyRow[];
            by_category: CategoryRow[];
            by_account: AccountRow[];
        };
    }>(),
    {
        filters: undefined,
        currency: undefined,
        currencyOptions: () => [],
        report: undefined,
    },
);

const symbol = computed(() => props.currency?.symbol ?? '');

function money(value: string): string {
    return `${symbol.value} ${Number(value).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;
}

// Every filter change is a fresh server render: the aggregation belongs on the
// backend, so the page never recomputes totals in the browser.
function applyFilter(patch: Record<string, string>): void {
    router.get(
        reportsIndex().url,
        {
            from: props.filters?.from ?? '',
            to: props.filters?.to ?? '',
            currency_id: String(props.filters?.currency_id ?? ''),
            ...patch,
        },
        { preserveScroll: true, preserveState: true, replace: true },
    );
}

// A plain link, not an Inertia visit: the response is a file, and a visit would
// try to parse it as a page. The filters ride along so the PDF covers exactly
// the period on screen.
const exportUrl = computed(
    () =>
        exportReport({
            query: {
                from: props.filters?.from ?? '',
                to: props.filters?.to ?? '',
                currency_id: String(props.filters?.currency_id ?? ''),
            },
        }).url,
);

const netClass = (value: string) => (Number(value) < 0 ? 'text-red-600' : 'text-emerald-600');

// The report is month-based but DatePicker speaks full dates: show the first of
// the month, and snap whatever day the user picks back to its month.
function monthAsDate(month: string): string {
    return `${month}-01`;
}

function dateAsMonth(date: string): string {
    return date.slice(0, 7);
}
</script>

<template>
    <Head :title="$t('reports.title')" />

    <DefaultLayout>
        <main class="flex flex-col gap-5 p-3 md:p-5">
            <PageHeader :title="$t('reports.title')" :subtitle="$t('reports.subtitle')">
                <Button v-if="ready" as-child variant="outline">
                    <a :href="exportUrl" download>
                        <DownloadIcon class="size-4" />
                        {{ $t('reports.export_pdf') }}
                    </a>
                </Button>
            </PageHeader>

            <Card v-if="!ready">
                <p class="text-sm text-muted-foreground">{{ $t('reports.empty') }}</p>
            </Card>

            <template v-else-if="report && filters">
                <Card>
                    <div class="flex flex-wrap items-end gap-4">
                        <div class="w-56">
                            <DatePicker
                                :model-value="monthAsDate(filters.from)"
                                :label="$t('reports.filters.from')"
                                @update:model-value="
                                    (value: string) => applyFilter({ from: dateAsMonth(value) })
                                "
                            />
                        </div>

                        <div class="w-56">
                            <DatePicker
                                :model-value="monthAsDate(filters.to)"
                                :label="$t('reports.filters.to')"
                                @update:model-value="
                                    (value: string) => applyFilter({ to: dateAsMonth(value) })
                                "
                            />
                        </div>

                        <label v-if="currencyOptions.length > 1" class="flex flex-col gap-1">
                            <span class="text-xs font-medium text-muted-foreground">
                                {{ $t('reports.filters.currency') }}
                            </span>
                            <Select
                                :model-value="String(filters.currency_id)"
                                @update:model-value="
                                    (value) => applyFilter({ currency_id: String(value) })
                                "
                            >
                                <SelectTrigger class="w-56">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in currencyOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </label>
                    </div>
                </Card>

                <Card>
                    <h2 class="mb-4 font-medium">{{ $t('reports.summary.title') }}</h2>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="flex flex-col gap-1">
                            <span class="text-xs text-muted-foreground">
                                {{ $t('reports.summary.income') }}
                            </span>
                            <span class="text-xl font-semibold text-emerald-600">
                                {{ money(report.summary.income_paid) }}
                            </span>
                            <span class="text-xs text-muted-foreground">
                                {{ money(report.summary.income) }}
                                {{ $t('reports.summary.expected') }}
                            </span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-xs text-muted-foreground">
                                {{ $t('reports.summary.expenses') }}
                            </span>
                            <span class="text-xl font-semibold text-red-600">
                                {{ money(report.summary.expenses_paid) }}
                            </span>
                            <span class="text-xs text-muted-foreground">
                                {{ money(report.summary.expenses) }}
                                {{ $t('reports.summary.expected') }}
                            </span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-xs text-muted-foreground">
                                {{ $t('reports.summary.net') }}
                            </span>
                            <span
                                class="text-xl font-semibold"
                                :class="netClass(report.summary.net_paid)"
                            >
                                {{ money(report.summary.net_paid) }}
                            </span>
                            <span class="text-xs text-muted-foreground">
                                {{ report.summary.entries }} {{ $t('reports.summary.entries') }}
                            </span>
                        </div>
                    </div>
                </Card>

                <Card>
                    <h2 class="mb-4 font-medium">{{ $t('reports.monthly.title') }}</h2>
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{{ $t('reports.monthly.month') }}</TableHead>
                                    <TableHead class="text-right">
                                        {{ $t('reports.summary.income') }}
                                    </TableHead>
                                    <TableHead class="text-right">
                                        {{ $t('reports.summary.expenses') }}
                                    </TableHead>
                                    <TableHead class="text-right">
                                        {{ $t('reports.summary.net') }}
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableEmpty v-if="report.monthly.length === 0" :colspan="4">
                                    {{ $t('reports.no_entries') }}
                                </TableEmpty>
                                <TableRow v-for="row in report.monthly" :key="row.month">
                                    <TableCell>{{ row.label }}</TableCell>
                                    <TableCell class="text-right">
                                        {{ money(row.income) }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        {{ money(row.expenses) }}
                                    </TableCell>
                                    <TableCell class="text-right" :class="netClass(row.net)">
                                        {{ money(row.net) }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </Card>

                <Card>
                    <h2 class="mb-4 font-medium">{{ $t('reports.by_category.title') }}</h2>
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{{ $t('reports.by_category.category') }}</TableHead>
                                    <TableHead>{{ $t('reports.by_category.type') }}</TableHead>
                                    <TableHead class="text-right">
                                        {{ $t('reports.by_category.total') }}
                                    </TableHead>
                                    <TableHead class="text-right">
                                        {{ $t('reports.by_category.share') }}
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableEmpty v-if="report.by_category.length === 0" :colspan="4">
                                    {{ $t('reports.no_entries') }}
                                </TableEmpty>
                                <TableRow
                                    v-for="(row, index) in report.by_category"
                                    :key="`${row.type}-${row.name}-${index}`"
                                >
                                    <TableCell>
                                        <span class="flex items-center gap-2">
                                            <span
                                                class="size-2.5 shrink-0 rounded-full"
                                                :style="{ backgroundColor: colorHex(row.color) }"
                                            />
                                            {{ row.name }}
                                        </span>
                                    </TableCell>
                                    <TableCell class="text-muted-foreground">
                                        {{ $t(`enums.transaction_movement_type.${row.type}`) }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        {{ money(row.total) }}
                                    </TableCell>
                                    <TableCell class="text-right text-muted-foreground">
                                        {{ row.share }}%
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </Card>

                <Card>
                    <h2 class="mb-4 font-medium">{{ $t('reports.by_account.title') }}</h2>
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{{ $t('reports.by_account.account') }}</TableHead>
                                    <TableHead class="text-right">
                                        {{ $t('reports.summary.income') }}
                                    </TableHead>
                                    <TableHead class="text-right">
                                        {{ $t('reports.summary.expenses') }}
                                    </TableHead>
                                    <TableHead class="text-right">
                                        {{ $t('reports.summary.net') }}
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableEmpty v-if="report.by_account.length === 0" :colspan="4">
                                    {{ $t('reports.no_entries') }}
                                </TableEmpty>
                                <TableRow v-for="row in report.by_account" :key="row.name">
                                    <TableCell>{{ row.name }}</TableCell>
                                    <TableCell class="text-right">
                                        {{ money(row.income) }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        {{ money(row.expenses) }}
                                    </TableCell>
                                    <TableCell class="text-right" :class="netClass(row.net)">
                                        {{ money(row.net) }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </Card>
            </template>
        </main>
    </DefaultLayout>
</template>
