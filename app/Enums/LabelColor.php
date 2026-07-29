<?php

namespace App\Enums;

/**
 * Shared palette for categories and tags. The hex values are used both for the
 * swatches in the UI and to color the dashboard charts, so they must stay stable.
 *
 * Ordered around the colour wheel with each hue beside its darker tier, so the
 * picker reads as a gradient rather than a scatter. Neutrals close the list.
 */
enum LabelColor: string
{
    case Red = 'red';

    case RedDark = 'red_dark';

    case Orange = 'orange';

    case OrangeDark = 'orange_dark';

    case Amber = 'amber';

    case AmberDark = 'amber_dark';

    case Yellow = 'yellow';

    case YellowDark = 'yellow_dark';

    case Lime = 'lime';

    case LimeDark = 'lime_dark';

    case Green = 'green';

    case GreenDark = 'green_dark';

    case Emerald = 'emerald';

    case EmeraldDark = 'emerald_dark';

    case Teal = 'teal';

    case TealDark = 'teal_dark';

    case Cyan = 'cyan';

    case CyanDark = 'cyan_dark';

    case Sky = 'sky';

    case SkyDark = 'sky_dark';

    case Blue = 'blue';

    case BlueDark = 'blue_dark';

    case Indigo = 'indigo';

    case IndigoDark = 'indigo_dark';

    case Violet = 'violet';

    case VioletDark = 'violet_dark';

    case Purple = 'purple';

    case PurpleDark = 'purple_dark';

    case Fuchsia = 'fuchsia';

    case FuchsiaDark = 'fuchsia_dark';

    case Pink = 'pink';

    case PinkDark = 'pink_dark';

    case Rose = 'rose';

    case RoseDark = 'rose_dark';

    case Slate = 'slate';

    case SlateDark = 'slate_dark';

    case Zinc = 'zinc';

    case ZincDark = 'zinc_dark';

    case Stone = 'stone';

    case StoneDark = 'stone_dark';

    public static function default(): self
    {
        return self::Zinc;
    }

    public function label(): string
    {
        return __('enums.label_color.'.$this->value);
    }

    public function hex(): string
    {
        return match ($this) {
            self::Red => '#ef4444',
            self::RedDark => '#b91c1c',
            self::Orange => '#f97316',
            self::OrangeDark => '#c2410c',
            self::Amber => '#f59e0b',
            self::AmberDark => '#b45309',
            self::Yellow => '#eab308',
            self::YellowDark => '#a16207',
            self::Lime => '#84cc16',
            self::LimeDark => '#4d7c0f',
            self::Green => '#22c55e',
            self::GreenDark => '#15803d',
            self::Emerald => '#10b981',
            self::EmeraldDark => '#047857',
            self::Teal => '#14b8a6',
            self::TealDark => '#0f766e',
            self::Cyan => '#06b6d4',
            self::CyanDark => '#0e7490',
            self::Sky => '#0ea5e9',
            self::SkyDark => '#0369a1',
            self::Blue => '#3b82f6',
            self::BlueDark => '#1d4ed8',
            self::Indigo => '#6366f1',
            self::IndigoDark => '#4338ca',
            self::Violet => '#8b5cf6',
            self::VioletDark => '#6d28d9',
            self::Purple => '#a855f7',
            self::PurpleDark => '#7e22ce',
            self::Fuchsia => '#d946ef',
            self::FuchsiaDark => '#a21caf',
            self::Pink => '#ec4899',
            self::PinkDark => '#be185d',
            self::Rose => '#f43f5e',
            self::RoseDark => '#be123c',
            self::Slate => '#64748b',
            self::SlateDark => '#334155',
            self::Zinc => '#71717a',
            self::ZincDark => '#3f3f46',
            self::Stone => '#78716c',
            self::StoneDark => '#44403c',
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
