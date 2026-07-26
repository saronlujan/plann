<?php

namespace App\Enums;

/**
 * Sound played when a transaction is marked as paid. The actual audio is
 * synthesized on the frontend (see resources/js/lib/sound.ts); these values
 * must stay in sync with the keys defined there.
 */
enum SoundTheme: string
{
    case Blip = 'blip';

    case Coin = 'coin';

    case Chime = 'chime';

    case Pop = 'pop';

    case Success = 'success';

    public function label(): string
    {
        return __('enums.sound_theme.'.$this->value);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $theme): array => ['value' => $theme->value, 'label' => $theme->label()],
            self::cases(),
        );
    }
}
