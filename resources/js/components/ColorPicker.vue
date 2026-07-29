<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Input } from '@/components/ui/input';
import { colorHex, DEFAULT_COLOR_HEX, isCustomColor, LABEL_COLORS } from '@/lib/labelColors';
import { cn } from '@/lib/utils';

const props = withDefaults(defineProps<{ size?: 'default' | 'lg' }>(), { size: 'default' });

const model = defineModel<string>({ required: true });

// The onboarding card is roomier than the modals, so the swatches can breathe
// there without changing how they look everywhere else.
const swatchSize = computed(() => (props.size === 'lg' ? 'size-6' : 'size-5'));

const isCustom = computed(() => isCustomColor(model.value));

/**
 * What the custom swatch shows and the native picker opens on. A palette colour
 * is still a starting point, so switching to custom does not jump to black.
 */
const customHex = ref(isCustom.value ? model.value.toLowerCase() : colorHex(model.value));

/** Free text while it is being typed: only a complete colour reaches the model. */
const draft = ref(customHex.value);

watch(model, (value) => {
    if (isCustomColor(value)) {
        customHex.value = value.toLowerCase();
        draft.value = customHex.value;
    }
});

function commit(value: string): void {
    const candidate = value.startsWith('#') ? value : `#${value}`;

    if (!isCustomColor(candidate)) {
        return;
    }

    customHex.value = candidate.toLowerCase();
    model.value = customHex.value;
}

/** Typing is committed as soon as it forms a colour, and ignored until then. */
function onDraftInput(value: string): void {
    draft.value = value;
    commit(value);
}

/** Leaving a half-typed value behind would strand the field: put it back. */
function onDraftBlur(): void {
    draft.value = isCustom.value ? model.value.toLowerCase() : customHex.value;
}

function selectCustom(): void {
    model.value = customHex.value;
}
</script>

<template>
    <div class="flex flex-col gap-3">
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

        <div class="flex items-center gap-2">
            <span class="text-sm text-muted-foreground">{{ $t('common.color.custom') }}</span>

            <!--
                The native input is the colour wheel: it costs nothing, it is
                keyboard reachable and it is the picker the user already knows.
                Sized to a swatch and given the selected ring like the presets.
            -->
            <label
                :class="
                    cn(
                        swatchSize,
                        'relative shrink-0 cursor-pointer rounded-full ring-offset-2 ring-offset-background transition',
                        isCustom ? 'ring-2 ring-foreground' : 'ring-1 ring-border hover:scale-110',
                    )
                "
                :style="{ backgroundColor: customHex }"
                :title="$t('common.color.custom')"
            >
                <input
                    type="color"
                    class="absolute inset-0 size-full cursor-pointer opacity-0"
                    :value="customHex"
                    :aria-label="$t('common.color.custom')"
                    @input="commit(($event.target as HTMLInputElement).value)"
                />
            </label>

            <Input
                :model-value="draft"
                class="h-8 w-28 font-mono text-sm"
                spellcheck="false"
                autocomplete="off"
                maxlength="7"
                :placeholder="DEFAULT_COLOR_HEX"
                :aria-label="$t('common.color.custom')"
                @update:model-value="(value) => onDraftInput(String(value))"
                @blur="onDraftBlur"
                @focus="selectCustom"
            />
        </div>
    </div>
</template>
