<script setup lang="ts">
import { LABEL_COLORS } from '@/lib/labelColors';
import { cn } from '@/lib/utils';

const model = defineModel<string>({ required: true });
</script>

<template>
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
                    'size-7 rounded-full ring-offset-2 ring-offset-background transition',
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
