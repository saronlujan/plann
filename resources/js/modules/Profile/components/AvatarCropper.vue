<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';

/**
 * Square crop picker: drag to reposition, slide to zoom.
 *
 * It only reports a region — the server does the actual cropping with the image
 * library, so what gets stored never depends on bytes the browser produced.
 */
const props = defineProps<{
    file: File | null;
    /** Side of the preview box in CSS pixels. */
    size?: number;
}>();

const emit = defineEmits<{
    /** Region in the source image's own pixels. */
    change: [crop: { x: number; y: number; size: number }];
}>();

const boxSize = computed(() => props.size ?? 240);

const objectUrl = ref<string | null>(null);
const naturalWidth = ref(0);
const naturalHeight = ref(0);
const zoom = ref(1);

// Offset of the image's top-left corner relative to the box, in CSS pixels.
const offsetX = ref(0);
const offsetY = ref(0);

let dragStartX = 0;
let dragStartY = 0;
let dragging = false;

function releaseObjectUrl(): void {
    if (objectUrl.value !== null) {
        URL.revokeObjectURL(objectUrl.value);
        objectUrl.value = null;
    }
}

onBeforeUnmount(releaseObjectUrl);

/** Scale that makes the shorter side exactly fill the box. */
const baseScale = computed(() => {
    if (naturalWidth.value === 0 || naturalHeight.value === 0) {
        return 1;
    }

    return boxSize.value / Math.min(naturalWidth.value, naturalHeight.value);
});

const scale = computed(() => baseScale.value * zoom.value);

const renderedWidth = computed(() => naturalWidth.value * scale.value);
const renderedHeight = computed(() => naturalHeight.value * scale.value);

/** Keeps the box covered: the image may never expose an empty corner. */
function clampOffsets(): void {
    offsetX.value = Math.min(0, Math.max(boxSize.value - renderedWidth.value, offsetX.value));
    offsetY.value = Math.min(0, Math.max(boxSize.value - renderedHeight.value, offsetY.value));
}

function emitCrop(): void {
    if (naturalWidth.value === 0) {
        return;
    }

    // CSS pixels back to source pixels — the region the server will cut.
    emit('change', {
        x: Math.round(-offsetX.value / scale.value),
        y: Math.round(-offsetY.value / scale.value),
        size: Math.round(boxSize.value / scale.value),
    });
}

function centreImage(): void {
    offsetX.value = (boxSize.value - renderedWidth.value) / 2;
    offsetY.value = (boxSize.value - renderedHeight.value) / 2;
    clampOffsets();
    emitCrop();
}

function onImageLoad(event: Event): void {
    const image = event.target as HTMLImageElement;

    naturalWidth.value = image.naturalWidth;
    naturalHeight.value = image.naturalHeight;
    zoom.value = 1;
    centreImage();
}

watch(
    () => props.file,
    (file) => {
        releaseObjectUrl();
        naturalWidth.value = 0;
        naturalHeight.value = 0;

        if (file) {
            objectUrl.value = URL.createObjectURL(file);
        }
    },
    { immediate: true },
);

watch(zoom, (next, previous) => {
    if (previous === 0) {
        return;
    }

    // Zoom around the middle of the box so the framing stays where it was.
    const ratio = next / previous;
    const centre = boxSize.value / 2;

    offsetX.value = centre - (centre - offsetX.value) * ratio;
    offsetY.value = centre - (centre - offsetY.value) * ratio;
    clampOffsets();
    emitCrop();
});

function startDrag(event: PointerEvent): void {
    dragging = true;
    dragStartX = event.clientX - offsetX.value;
    dragStartY = event.clientY - offsetY.value;
    (event.currentTarget as HTMLElement).setPointerCapture(event.pointerId);
}

function onDrag(event: PointerEvent): void {
    if (!dragging) {
        return;
    }

    offsetX.value = event.clientX - dragStartX;
    offsetY.value = event.clientY - dragStartY;
    clampOffsets();
}

function endDrag(event: PointerEvent): void {
    if (!dragging) {
        return;
    }

    dragging = false;
    (event.currentTarget as HTMLElement).releasePointerCapture(event.pointerId);
    emitCrop();
}
</script>

<template>
    <div v-if="objectUrl" class="flex flex-col items-center gap-3">
        <div
            class="relative touch-none overflow-hidden rounded-full border-2 border-dashed border-zinc-300 select-none dark:border-zinc-700"
            :style="{ width: `${boxSize}px`, height: `${boxSize}px`, cursor: 'grab' }"
            @pointerdown="startDrag"
            @pointermove="onDrag"
            @pointerup="endDrag"
            @pointercancel="endDrag"
        >
            <img
                :src="objectUrl"
                alt=""
                draggable="false"
                class="max-w-none origin-top-left"
                :style="{
                    width: `${renderedWidth}px`,
                    height: `${renderedHeight}px`,
                    transform: `translate(${offsetX}px, ${offsetY}px)`,
                }"
                @load="onImageLoad"
            />
        </div>

        <input
            v-model.number="zoom"
            type="range"
            min="1"
            max="4"
            step="0.01"
            class="w-full max-w-60 accent-zinc-900 dark:accent-zinc-100"
            :aria-label="$t('profile.avatar.zoom')"
        />
    </div>
</template>
