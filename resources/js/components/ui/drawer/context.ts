import type { ComputedRef, InjectionKey, Ref } from 'vue';

export interface DrawerContextValue {
    open: ComputedRef<boolean>;
    direction: Ref<'top' | 'right' | 'bottom' | 'left'>;
    setOpen: (value: boolean) => void;
    close: () => void;
    toggle: () => void;
}

export const drawerContextKey: InjectionKey<DrawerContextValue> = Symbol('drawer-context');
