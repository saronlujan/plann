import type { VariantProps } from 'class-variance-authority';
import { cva } from 'class-variance-authority';

export { default as Switch } from './Switch.vue';

export const switchVariants = cva(
    'peer inline-flex shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent shadow-xs transition-colors focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 data-[state=checked]:bg-primary data-[state=unchecked]:bg-input',
    {
        variants: {
            size: {
                default: 'h-6 w-11',
                sm: 'h-5 w-9',
            },
        },
        defaultVariants: {
            size: 'default',
        },
    },
);

export const switchThumbVariants = cva(
    'pointer-events-none block rounded-full bg-background shadow-lg ring-0 transition-transform data-[state=unchecked]:translate-x-0',
    {
        variants: {
            size: {
                default: 'size-5 data-[state=checked]:translate-x-5',
                sm: 'size-4 data-[state=checked]:translate-x-4',
            },
        },
        defaultVariants: {
            size: 'default',
        },
    },
);

export type SwitchVariants = VariantProps<typeof switchVariants>;
