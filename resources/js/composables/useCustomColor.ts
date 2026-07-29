import { computed, ref, watch } from 'vue';
import { colorHex, isCustomColor } from '@/lib/labelColors';

/**
 * The state behind a hand-picked colour: the swatch, the half-typed hex, and the
 * rule for when typing becomes a real value.
 *
 * Shared by the label palette and the appearance accent. The two look nothing
 * alike, so only the behaviour is lifted here — a component would have had to
 * carry both skins and their differences as props.
 *
 * @param current  Reads the colour in force, palette name or hex.
 * @param commit   Called with a normalised `#rrggbb` once one is complete.
 */
export function useCustomColor(current: () => string, commit: (hex: string) => void) {
    const isCustom = computed(() => isCustomColor(current()));

    // What the swatch shows and the native picker opens on. A palette colour is
    // still a starting point, so switching to custom does not jump to black.
    const hex = ref(isCustom.value ? current().toLowerCase() : colorHex(current()));

    /** Free text while it is being typed: only a complete colour is committed. */
    const draft = ref(hex.value);

    // Someone else may set the colour — another swatch, a fresh page. Follow it
    // so the field never shows a value the app has moved on from.
    watch(current, (value) => {
        if (isCustomColor(value)) {
            hex.value = value.toLowerCase();
            draft.value = hex.value;
        }
    });

    /** Accepts `6361f3` as readily as `#6361f3`; anything shorter is still typing. */
    function apply(value: string): void {
        const candidate = value.startsWith('#') ? value : `#${value}`;

        if (!isCustomColor(candidate)) {
            return;
        }

        hex.value = candidate.toLowerCase();
        draft.value = hex.value;
        commit(hex.value);
    }

    /** Typing is committed as soon as it forms a colour, and ignored until then. */
    function onInput(value: string): void {
        draft.value = value;
        apply(value);
    }

    /** Leaving a half-typed value behind would strand the field: put it back. */
    function onBlur(): void {
        draft.value = hex.value;
    }

    /** Focusing the field is a choice: adopt the swatch as the colour in force. */
    function select(): void {
        commit(hex.value);
    }

    return { isCustom, hex, draft, apply, onInput, onBlur, select };
}
