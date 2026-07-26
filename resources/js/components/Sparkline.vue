<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{ points: number[] }>();

const WIDTH = 100;
const HEIGHT = 32;

const path = computed(() => {
    const pts = props.points;

    if (!pts || pts.length < 2) {
        return '';
    }

    const min = Math.min(...pts);
    const max = Math.max(...pts);
    const range = max - min || 1;
    const step = WIDTH / (pts.length - 1);

    return pts
        .map((value, index) => {
            const x = index * step;
            const y = HEIGHT - ((value - min) / range) * HEIGHT;

            return `${index === 0 ? 'M' : 'L'}${x.toFixed(1)},${y.toFixed(1)}`;
        })
        .join(' ');
});

const trendUp = computed(() => {
    const pts = props.points;

    return pts.length >= 2 && pts[pts.length - 1] >= pts[0];
});
</script>

<template>
    <svg
        v-if="path"
        :viewBox="`0 0 ${WIDTH} ${HEIGHT}`"
        preserveAspectRatio="none"
        class="h-8 w-20"
        fill="none"
        aria-hidden="true"
    >
        <path
            :d="path"
            :class="trendUp ? 'stroke-emerald-500' : 'stroke-red-500'"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            vector-effect="non-scaling-stroke"
        />
    </svg>
</template>
