<script setup lang="ts">
import type { ApexOptions } from 'apexcharts';
import { trans } from 'laravel-vue-i18n';
import { computed, defineAsyncComponent, onMounted, ref } from 'vue';
import { formatMoney } from '@/lib/money';

// Loaded lazily (client-only) so ApexCharts' window usage never runs during SSR.
const ApexChart = defineAsyncComponent(() => import('vue3-apexcharts'));

type Point = { label: string; income: string; expense: string };

const props = defineProps<{ series: Point[]; currency: string }>();

const mounted = ref(false);
onMounted(() => (mounted.value = true));

function isDark(): boolean {
    return typeof document !== 'undefined' && document.documentElement.classList.contains('dark');
}

const chartSeries = computed(() => [
    { name: trans('dashboard.income'), data: props.series.map((p) => Number.parseFloat(p.income)) },
    {
        name: trans('dashboard.expenses'),
        data: props.series.map((p) => Number.parseFloat(p.expense)),
    },
]);

const chartOptions = computed((): ApexOptions => ({
    chart: {
        type: 'area',
        toolbar: { show: false },
        zoom: { enabled: false },
        fontFamily: 'inherit',
        foreColor: isDark() ? '#a1a1aa' : '#71717a',
        background: 'transparent',
    },
    theme: { mode: isDark() ? 'dark' : 'light' },
    colors: ['#10b981', '#f97316'],
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 2.5 },
    fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.02 } },
    grid: { borderColor: isDark() ? '#27272a' : '#f4f4f5', strokeDashArray: 4 },
    xaxis: {
        categories: props.series.map((p) => p.label),
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: { labels: { formatter: (value: number) => formatMoney(value, props.currency) } },
    legend: { position: 'top', horizontalAlign: 'left' },
    tooltip: { y: { formatter: (value: number) => formatMoney(value, props.currency) } },
}));
</script>

<template>
    <div class="min-h-55">
        <ApexChart
            v-if="mounted"
            type="area"
            height="220"
            :options="chartOptions"
            :series="chartSeries"
        />
    </div>
</template>
