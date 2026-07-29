<?php

namespace App\Enums;

use Closure;

/**
 * The accent the interface is tinted with. The shades themselves live in
 * resources/js/composables/useAppearance.ts, which overrides the shadcn
 * `--primary` tokens — these names only say which one is wanted.
 *
 * As with a label, the stored value is either one of these names or a plain
 * `#rrggbb`, and the leading hash is what tells them apart.
 */
enum UserColor: string
{
    case Blue = 'blue';

    case Indigo = 'indigo';

    case Purple = 'purple';

    case Pink = 'pink';

    case Cyan = 'cyan';

    case Orange = 'orange';

    case Yellow = 'yellow';

    case Green = 'green';

    case Teal = 'teal';

    case Zinc = 'zinc';

    public function label(): string
    {
        return __('enums.user_color.'.$this->value);
    }

    /**
     * An accent chosen by hand rather than taken from the palette.
     */
    public static function isCustom(string $color): bool
    {
        return preg_match('/^#[0-9a-f]{6}$/', mb_strtolower($color)) === 1;
    }

    /**
     * What the accent field accepts: a name from the palette, or a `#rrggbb`.
     *
     * @return array<int, mixed>
     */
    public static function validationRules(): array
    {
        return [
            'required',
            'string',
            'max:20',
            function (string $attribute, mixed $value, Closure $fail): void {
                if (is_string($value) && (self::tryFrom($value) !== null || self::isCustom($value))) {
                    return;
                }

                $fail(__('validation.label_color', ['attribute' => $attribute]));
            },
        ];
    }

    /**
     * Hand-picked accents are folded to lowercase, so the same colour is never
     * stored two ways depending on how it was typed.
     */
    public static function normalize(string $color): string
    {
        return self::isCustom($color) ? mb_strtolower($color) : $color;
    }
}
