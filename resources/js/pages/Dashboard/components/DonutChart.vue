<script setup lang="ts">
import type { ApexOptions } from 'apexcharts';
import { trans } from 'laravel-vue-i18n';
import { computed, defineAsyncComponent, onMounted, ref } from 'vue';
import { colorHex } from '@/lib/labelColors';
import { formatMoney } from '@/lib/money';

// Loaded lazily (client-only) so ApexCharts' window usage never runs during SSR.
const ApexChart = defineAsyncComponent(() => import('vue3-apexcharts'));

type Segment = { name: string; color: string; value: string };

const props = defineProps<{ segments: Segment[]; currency: string }>();

const mounted = ref(false);
onMounted(() => (mounted.value = true));

function isDark(): boolean {
    return typeof document !== 'undefined' && document.documentElement.classList.contains('dark');
}

const total = computed(() =>
    props.segments.reduce((sum, segment) => sum + Number.parseFloat(segment.value), 0),
);

const chartSeries = computed(() => props.segments.map((segment) => Number.parseFloat(segment.value)));

const chartOptions = computed((): ApexOptions => ({
    chart: {
        type: 'donut',
        fontFamily: 'inherit',
        foreColor: isDark() ? '#a1a1aa' : '#71717a',
        background: 'transparent',
    },
    theme: { mode: isDark() ? 'dark' : 'light' },
    labels: props.segments.map((segment) => segment.name),
    colors: props.segments.map((segment) => colorHex(segment.color)),
    legend: { position: 'bottom' },
    stroke: { width: 0 },
    dataLabels: { enabled: true, formatter: (value: number) => `${Math.round(value)}%` },
    plotOptions: {
        pie: {
            donut: {
                size: '70%',
                labels: {
                    show: true,
                    total: {
                        show: true,
                        label: trans('dashboard.total'),
                        formatter: () => formatMoney(total.value, props.currency),
                    },
                    value: {
                        formatter: (value: string) => formatMoney(value, props.currency),
                    },
                },
            },
        },
    },
    tooltip: { y: { formatter: (value: number) => formatMoney(value, props.currency) } },
}));
</script>

<template>
    <div class="min-h-60">
        <div v-if="total === 0" class="py-8 text-center text-sm text-muted-foreground">
            {{ $t('dashboard.no_expenses') }}
        </div>
        <ApexChart
            v-else-if="mounted"
            type="donut"
            height="260"
            :options="chartOptions"
            :series="chartSeries"
        />
    </div>
</template>
