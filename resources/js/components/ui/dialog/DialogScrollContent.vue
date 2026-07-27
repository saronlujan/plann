<script setup lang="ts">
import { X } from '@lucide/vue';
import { reactiveOmit } from '@vueuse/core';
import {
    DialogClose,
    DialogContent,
    type DialogContentEmits,
    type DialogContentProps,
    DialogOverlay,
    DialogPortal,
    injectDialogRootContext,
    type PointerDownOutsideEvent,
    useForwardPropsEmits,
} from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

/**
 * A dialog that scrolls around itself instead of inside itself.
 *
 * `DialogContent` pins itself to the middle of the viewport, so a form taller
 * than the screen simply gets cut off. Here a wrapper takes the scroll and the
 * dialog grows as tall as it needs.
 */
defineOptions({
    inheritAttrs: false,
});

const props = withDefaults(
    defineProps<
        DialogContentProps & { class?: HTMLAttributes['class']; showCloseButton?: boolean }
    >(),
    {
        showCloseButton: true,
    },
);
const emits = defineEmits<DialogContentEmits>();

const delegatedProps = reactiveOmit(props, 'class', 'showCloseButton');

const forwarded = useForwardPropsEmits(delegatedProps, emits);

/**
 * `DialogPortal` is a bare Teleport: it renders its slot whether the dialog is
 * open or not. The overlay and the content unmount themselves, but a plain
 * wrapper would not — it would sit over the whole app swallowing every click.
 */
const rootContext = injectDialogRootContext();

/**
 * A press on the scrollbar lands outside the dialog and would close it, which
 * makes a scrollable dialog impossible to scroll by dragging. Clicks past the
 * client box are the scrollbar, so they are ignored.
 */
function keepOpenOnScrollbarPress(event: PointerDownOutsideEvent): void {
    const originalEvent = event.detail.originalEvent;
    const target = originalEvent.target as HTMLElement;

    if (originalEvent.offsetX > target.clientWidth || originalEvent.offsetY > target.clientHeight) {
        event.preventDefault();
    }
}
</script>

<template>
    <DialogPortal>
        <!--
            The scroll belongs to this wrapper, not to the backdrop: the overlay
            stays a fixed sheet over the viewport while the dialog scrolls past it.
            It is mounted only while open — see rootContext above.
        -->
        <div v-if="rootContext.open.value" class="fixed inset-0 z-50 overflow-y-auto">
            <DialogOverlay
                data-slot="dialog-overlay"
                class="fixed inset-0 bg-black/80 data-[state=closed]:animate-out data-[state=open]:animate-in data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0"
            />

            <!--
                min-h-full with the padding here is what keeps a tall dialog
                reachable: centering alone would push its top out of the
                scrollable area.
            -->
            <div class="relative flex min-h-full items-center justify-center p-4">
                <DialogContent
                    data-slot="dialog-content"
                    v-bind="{ ...$attrs, ...forwarded }"
                    :class="
                        cn(
                            'relative z-50 grid w-full max-w-[calc(100%-2rem)] gap-6 rounded-lg border bg-background p-6 shadow-lg duration-200 data-[state=closed]:animate-out data-[state=open]:animate-in data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 sm:max-w-lg',
                            props.class,
                        )
                    "
                    @pointer-down-outside="keepOpenOnScrollbarPress"
                >
                    <slot />

                    <DialogClose v-if="props.showCloseButton" data-slot="dialog-close" as-child>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            class="absolute top-4 right-4 rounded-xs opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-hidden disabled:pointer-events-none data-[state=open]:bg-accent data-[state=open]:text-muted-foreground [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4"
                        >
                            <X />
                            <span class="sr-only">Close</span>
                        </Button>
                    </DialogClose>
                </DialogContent>
            </div>
        </div>
    </DialogPortal>
</template>
