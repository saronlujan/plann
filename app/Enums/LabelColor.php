<?php

namespace App\Enums;

/**
 * Shared palette for categories and tags. The hex values are used both for the
 * swatches in the UI and to color the dashboard charts, so they must stay stable.
 */
enum LabelColor: string
{
    case Blue = 'blue';

    case Indigo = 'indigo';

    case Purple = 'purple';

    case Pink = 'pink';

    case Red = 'red';

    case Orange = 'orange';

    case Yellow = 'yellow';

    case Green = 'green';

    case Teal = 'teal';

    case Zinc = 'zinc';

    public static function default(): self
    {
        return self::Zinc;
    }

    public function label(): string
    {
        return __('enums.label_color.'.$this->value);
    }

    /**
     * Chart/UI hex value (Tailwind 500 shade) for this color.
     */
    public function hex(): string
    {
        return match ($this) {
            self::Blue => '#3b82f6',
            self::Indigo => '#6366f1',
            self::Purple => '#a855f7',
            self::Pink => '#ec4899',
            self::Red => '#ef4444',
            self::Orange => '#f97316',
            self::Yellow => '#eab308',
            self::Green => '#22c55e',
            self::Teal => '#14b8a6',
            self::Zinc => '#71717a',
        };
    }

    /**
     * Selectable options for the frontend: value, label and hex.
     *
     * @return array<int, array{value: string, label: string, hex: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $color): array => [
                'value' => $color->value,
                'label' => $color->label(),
                'hex' => $color->hex(),
            ],
            self::cases(),
        );
    }
}
