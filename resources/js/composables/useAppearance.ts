/**
 * Applies the user's appearance preferences at runtime.
 *
 * Theme toggles the `.dark` class on <html> (shadcn dark variant). The color
 * palette overrides the shadcn `--primary` / `--primary-foreground` tokens, which
 * every accent detail already consumes. "zinc" is the default: it clears the
 * overrides so the theme-aware values from resources/css/main.css apply.
 *
 * The accent is not limited to the palette — a plain `#rrggbb` is accepted too,
 * and its foreground is computed rather than assumed, since a colour picked by
 * hand can easily be too light to read white lettering on.
 */
import { isCustomColor } from '@/lib/labelColors';

export type ThemeValue = 'system' | 'light' | 'dark';
export type ColorValue =
    | 'blue'
    | 'indigo'
    | 'purple'
    | 'pink'
    | 'cyan'
    | 'orange'
    | 'yellow'
    | 'green'
    | 'teal'
    | 'zinc';

type PaletteEntry = { primary: string; foreground: string };

const palette: Record<ColorValue, PaletteEntry | null> = {
    zinc: null,
    blue: { primary: 'oklch(0.55 0.22 260)', foreground: 'oklch(0.985 0 0)' },
    indigo: { primary: 'oklch(0.51 0.23 277)', foreground: 'oklch(0.985 0 0)' },
    purple: { primary: 'oklch(0.52 0.24 300)', foreground: 'oklch(0.985 0 0)' },
    pink: { primary: 'oklch(0.62 0.23 350)', foreground: 'oklch(0.985 0 0)' },
    cyan: { primary: 'oklch(0.72 0.13 210)', foreground: 'oklch(0.985 0 0)' },
    orange: { primary: 'oklch(0.70 0.18 55)', foreground: 'oklch(0.985 0 0)' },
    yellow: { primary: 'oklch(0.85 0.16 90)', foreground: 'oklch(0.985 0 0)' },
    green: { primary: 'oklch(0.62 0.17 150)', foreground: 'oklch(0.985 0 0)' },
    teal: { primary: 'oklch(0.60 0.13 190)', foreground: 'oklch(0.985 0 0)' },
};

/**
 * Relative luminance, per WCAG. Used to decide what can be read on top of an
 * accent the user picked: unlike the palette, an arbitrary colour may well be
 * light enough that white lettering disappears into it.
 */
function luminance(hex: string): number {
    const channels = [1, 3, 5].map((offset) => {
        const value = Number.parseInt(hex.slice(offset, offset + 2), 16) / 255;

        return value <= 0.03928 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4;
    });

    return 0.2126 * channels[0] + 0.7152 * channels[1] + 0.0722 * channels[2];
}

/** Whichever of white or near-black stands out more against the accent. */
function readableForeground(hex: string): string {
    const contrastWithWhite = 1.05 / (luminance(hex) + 0.05);
    const contrastWithBlack = (luminance(hex) + 0.05) / 0.05;

    return contrastWithWhite >= contrastWithBlack ? 'oklch(0.985 0 0)' : 'oklch(0.205 0 0)';
}

function customEntry(hex: string): PaletteEntry {
    return { primary: hex.toLowerCase(), foreground: readableForeground(hex.toLowerCase()) };
}

/** The stored accent as the swatch the picker paints. */
export function paletteSwatch(color: string): string {
    if (isCustomColor(color)) {
        return color.toLowerCase();
    }

    return palette[color as ColorValue]?.primary ?? 'oklch(0.205 0 0)';
}

const DARK_QUERY = '(prefers-color-scheme: dark)';

/** What "system" resolves to right now. */
function prefersDark(): boolean {
    return typeof window !== 'undefined' && window.matchMedia(DARK_QUERY).matches;
}

/**
 * The device can change its mind — sunset, a scheduled switch, a manual toggle —
 * and someone deferring to it expects the page to follow without a reload.
 * Registered once, and only while "system" is the choice.
 */
let unwatchSystem: (() => void) | undefined;

function watchSystemTheme(active: boolean): void {
    unwatchSystem?.();
    unwatchSystem = undefined;

    if (!active || typeof window === 'undefined') {
        return;
    }

    const query = window.matchMedia(DARK_QUERY);
    const onChange = (event: MediaQueryListEvent): void => {
        document.documentElement.classList.toggle('dark', event.matches);
    };

    query.addEventListener('change', onChange);
    unwatchSystem = () => query.removeEventListener('change', onChange);
}

export function applyAppearance(theme: ThemeValue = 'system', color: string = 'zinc'): void {
    if (typeof document === 'undefined') {
        return;
    }

    const root = document.documentElement;

    root.classList.toggle('dark', theme === 'system' ? prefersDark() : theme === 'dark');
    watchSystemTheme(theme === 'system');

    // "zinc" maps to null: it clears the overrides so the theme-aware defaults
    // from resources/css/main.css take over again.
    const entry = isCustomColor(color) ? customEntry(color) : (palette[color as ColorValue] ?? null);

    if (entry) {
        root.style.setProperty('--primary', entry.primary);
        root.style.setProperty('--primary-foreground', entry.foreground);
    } else {
        root.style.removeProperty('--primary');
        root.style.removeProperty('--primary-foreground');
    }
}
