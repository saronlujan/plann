// Mirrors App\Enums\LabelColor. Single source of truth for category/tag colors
// on the frontend — swatches, dots and dashboard charts all read from here.

export type LabelColorValue =
    'blue' | 'indigo' | 'purple' | 'pink' | 'red' | 'orange' | 'yellow' | 'green' | 'teal' | 'zinc';

export type LabelColorOption = {
    value: LabelColorValue;
    label: string;
    hex: string;
};

export const LABEL_COLORS: LabelColorOption[] = [
    { value: 'blue', label: 'Azul', hex: '#3b82f6' },
    { value: 'indigo', label: 'Índigo', hex: '#6366f1' },
    { value: 'purple', label: 'Roxo', hex: '#a855f7' },
    { value: 'pink', label: 'Rosa', hex: '#ec4899' },
    { value: 'red', label: 'Vermelho', hex: '#ef4444' },
    { value: 'orange', label: 'Laranja', hex: '#f97316' },
    { value: 'yellow', label: 'Amarelo', hex: '#eab308' },
    { value: 'green', label: 'Verde', hex: '#22c55e' },
    { value: 'teal', label: 'Turquesa', hex: '#14b8a6' },
    { value: 'zinc', label: 'Cinza', hex: '#71717a' },
];

const HEX_BY_VALUE = new Map(LABEL_COLORS.map((color) => [color.value, color.hex]));

export function colorHex(value: string | null | undefined): string {
    return HEX_BY_VALUE.get(value as LabelColorValue) ?? '#71717a';
}
