<script setup lang="ts">
import { computed } from 'vue';
import { LABEL_COLORS } from '@/lib/labelColors';
import { cn } from '@/lib/utils';

const props = withDefaults(defineProps<{ size?: 'default' | 'lg' }>(), { size: 'default' });

const model = defineModel<string>({ required: true });

// The onboarding card is roomier than the modals, so the swatches can breathe
// there without changing how they look everywhere else.
const swatchSize = computed(() => (props.size === 'lg' ? 'size-6' : 'size-5'));
</script>

<template>
    <!--
        Fixed circles rather than a stretched grid: sized off the container, the
        same swatches came out twice as large in a wide form as in a modal.
    -->
    <div class="flex flex-wrap gap-2">
        <button
            v-for="color in LABEL_COLORS"
            :key="color.value"
            type="button"
            :title="color.label"
            :aria-label="color.label"
            :aria-pressed="model === color.value"
            :class="
                cn(
                    swatchSize,
                    'shrink-0 rounded-full ring-offset-2 ring-offset-background transition',
                    model === color.value
                        ? 'ring-2 ring-foreground'
                        : 'ring-1 ring-border hover:scale-110',
                )
            "
            :style="{ backgroundColor: color.hex }"
            @click="model = color.value"
        />
    </div>
</template>
