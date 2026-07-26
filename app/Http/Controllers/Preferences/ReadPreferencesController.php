<?php

namespace App\Http\Controllers\Preferences;

use App\Enums\SoundTheme;
use App\Enums\UserColor;
use App\Enums\UserTheme;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReadPreferencesController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Preferences/Index', [
            'preferences' => [
                'locale' => $user->locale ?? 'pt',
                'theme' => $user?->theme->value ?? UserTheme::Light->value,
                'color' => $user?->color->value ?? UserColor::Zinc->value,
                'sound_enabled' => $user->sound_enabled ?? true,
                'sound_theme' => $user->sound_theme ?? SoundTheme::Blip->value,
                'notifications_enabled' => $user->notifications_enabled ?? false,
                'notify_days_before' => $user->notify_days_before ?? 3,
            ],
            'localeOptions' => [
                ['value' => 'pt', 'label' => 'Português'],
                ['value' => 'en', 'label' => 'English'],
                ['value' => 'es', 'label' => 'Español'],
            ],
            'themeOptions' => array_map(
                fn (UserTheme $theme): array => ['value' => $theme->value, 'label' => $theme->label()],
                UserTheme::cases(),
            ),
            'colorOptions' => array_map(
                fn (UserColor $color): array => ['value' => $color->value, 'label' => $color->label()],
                UserColor::cases(),
            ),
            'soundOptions' => SoundTheme::options(),
        ]);
    }
}
