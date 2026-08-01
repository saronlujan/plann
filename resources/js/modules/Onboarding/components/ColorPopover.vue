<script setup lang="ts">
import ColorPicker from '@/components/ColorPicker.vue';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { colorHex } from '@/lib/labelColors';

/**
 * The chosen colour as a swatch beside the field, with the palette behind it.
 *
 * Forty circles laid out flat push the rest of the step below the fold; here the
 * choice reads as one dot and the grid only appears while it is being made.
 */
const model = defineModel<string>({ required: true });
</script>

<template>
    <Popover>
        <PopoverTrigger as-child>
            <!--
                The swatch itself is the control: sized to the input's height so
                the two sit on one line, and named only for screen readers — the
                colour is the label.
            -->
            <button
                type="button"
                class="size-9 shrink-0 rounded-full border border-input shadow-xs transition-shadow outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                :style="{ backgroundColor: colorHex(model) }"
                :aria-label="$t('onboarding.color')"
            />
        </PopoverTrigger>

        <PopoverContent align="start" class="w-72">
            <ColorPicker v-model="model" size="lg" />
        </PopoverContent>
    </Popover>
</template>
