/**
 * Applies the user's appearance preferences at runtime.
 *
 * Theme toggles the `.dark` class on <html> (shadcn dark variant). The color
 * palette overrides the shadcn `--primary` / `--primary-foreground` tokens, which
 * every accent detail already consumes. "zinc" is the default: it clears the
 * overrides so the theme-aware values from resources/css/app.css apply.
 */
export type ThemeValue = 'light' | 'dark';
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

export function paletteSwatch(color: ColorValue): string {
    return palette[color]?.primary ?? 'oklch(0.205 0 0)';
}

export function applyAppearance(theme: ThemeValue = 'light', color: ColorValue = 'zinc'): void {
    if (typeof document === 'undefined') {
        return;
    }

    const root = document.documentElement;

    root.classList.toggle('dark', theme === 'dark');

    const entry = palette[color] ?? null;

    if (entry) {
        root.style.setProperty('--primary', entry.primary);
        root.style.setProperty('--primary-foreground', entry.foreground);
    } else {
        root.style.removeProperty('--primary');
        root.style.removeProperty('--primary-foreground');
    }
}
