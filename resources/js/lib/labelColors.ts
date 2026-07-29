// Mirrors App\Enums\LabelColor. Single source of truth for category/tag colors
// on the frontend — swatches, dots and dashboard charts all read from here.

export type LabelColorValue =
    | 'red'
    | 'red_dark'
    | 'orange'
    | 'orange_dark'
    | 'amber'
    | 'amber_dark'
    | 'yellow'
    | 'yellow_dark'
    | 'lime'
    | 'lime_dark'
    | 'green'
    | 'green_dark'
    | 'emerald'
    | 'emerald_dark'
    | 'teal'
    | 'teal_dark'
    | 'cyan'
    | 'cyan_dark'
    | 'sky'
    | 'sky_dark'
    | 'blue'
    | 'blue_dark'
    | 'indigo'
    | 'indigo_dark'
    | 'violet'
    | 'violet_dark'
    | 'purple'
    | 'purple_dark'
    | 'fuchsia'
    | 'fuchsia_dark'
    | 'pink'
    | 'pink_dark'
    | 'rose'
    | 'rose_dark'
    | 'slate'
    | 'slate_dark'
    | 'zinc'
    | 'zinc_dark'
    | 'stone'
    | 'stone_dark';

export type LabelColorOption = {
    value: LabelColorValue;
    label: string;
    hex: string;
};

export const LABEL_COLORS: LabelColorOption[] = [
    { value: 'red', label: 'Vermelho', hex: '#ef4444' },
    { value: 'red_dark', label: 'Vermelho escuro', hex: '#b91c1c' },
    { value: 'orange', label: 'Laranja', hex: '#f97316' },
    { value: 'orange_dark', label: 'Laranja escuro', hex: '#c2410c' },
    { value: 'amber', label: 'Âmbar', hex: '#f59e0b' },
    { value: 'amber_dark', label: 'Âmbar escuro', hex: '#b45309' },
    { value: 'yellow', label: 'Amarelo', hex: '#eab308' },
    { value: 'yellow_dark', label: 'Amarelo escuro', hex: '#a16207' },
    { value: 'lime', label: 'Lima', hex: '#84cc16' },
    { value: 'lime_dark', label: 'Lima escuro', hex: '#4d7c0f' },
    { value: 'green', label: 'Verde', hex: '#22c55e' },
    { value: 'green_dark', label: 'Verde escuro', hex: '#15803d' },
    { value: 'emerald', label: 'Esmeralda', hex: '#10b981' },
    { value: 'emerald_dark', label: 'Esmeralda escuro', hex: '#047857' },
    { value: 'teal', label: 'Turquesa', hex: '#14b8a6' },
    { value: 'teal_dark', label: 'Turquesa escuro', hex: '#0f766e' },
    { value: 'cyan', label: 'Ciano', hex: '#06b6d4' },
    { value: 'cyan_dark', label: 'Ciano escuro', hex: '#0e7490' },
    { value: 'sky', label: 'Céu', hex: '#0ea5e9' },
    { value: 'sky_dark', label: 'Céu escuro', hex: '#0369a1' },
    { value: 'blue', label: 'Azul', hex: '#3b82f6' },
    { value: 'blue_dark', label: 'Azul escuro', hex: '#1d4ed8' },
    { value: 'indigo', label: 'Índigo', hex: '#6366f1' },
    { value: 'indigo_dark', label: 'Índigo escuro', hex: '#4338ca' },
    { value: 'violet', label: 'Violeta', hex: '#8b5cf6' },
    { value: 'violet_dark', label: 'Violeta escuro', hex: '#6d28d9' },
    { value: 'purple', label: 'Roxo', hex: '#a855f7' },
    { value: 'purple_dark', label: 'Roxo escuro', hex: '#7e22ce' },
    { value: 'fuchsia', label: 'Fúcsia', hex: '#d946ef' },
    { value: 'fuchsia_dark', label: 'Fúcsia escuro', hex: '#a21caf' },
    { value: 'pink', label: 'Rosa', hex: '#ec4899' },
    { value: 'pink_dark', label: 'Rosa escuro', hex: '#be185d' },
    { value: 'rose', label: 'Rosé', hex: '#f43f5e' },
    { value: 'rose_dark', label: 'Rosé escuro', hex: '#be123c' },
    { value: 'slate', label: 'Ardósia', hex: '#64748b' },
    { value: 'slate_dark', label: 'Ardósia escuro', hex: '#334155' },
    { value: 'zinc', label: 'Cinza', hex: '#71717a' },
    { value: 'zinc_dark', label: 'Cinza escuro', hex: '#3f3f46' },
    { value: 'stone', label: 'Pedra', hex: '#78716c' },
    { value: 'stone_dark', label: 'Pedra escuro', hex: '#44403c' },
];

const HEX_BY_VALUE = new Map(LABEL_COLORS.map((color) => [color.value, color.hex]));

/** The neutral a colour falls back to when it names nothing recognisable. */
export const DEFAULT_COLOR_HEX = '#71717a';

const CUSTOM_PATTERN = /^#[0-9a-f]{6}$/i;

/**
 * A colour picked by hand rather than taken from the palette. Mirrors
 * App\Enums\LabelColor::isCustom() — the leading hash is what tells them apart.
 */
export function isCustomColor(value: string | null | undefined): boolean {
    return typeof value === 'string' && CUSTOM_PATTERN.test(value);
}

export function colorHex(value: string | null | undefined): string {
    if (isCustomColor(value)) {
        return (value as string).toLowerCase();
    }

    return HEX_BY_VALUE.get(value as LabelColorValue) ?? DEFAULT_COLOR_HEX;
}
