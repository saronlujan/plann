<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'locale' => $locale,
            'translations' => $this->translations($locale),
            'auth' => [
                'user' => $this->authUser($request),
            ],
        ];
    }

    /**
     * The signed-in user, with the picture resolved to something the browser can
     * load: an uploaded avatar is served by a route, an OAuth one is already a
     * URL. The version parameter busts the cache when the file changes.
     *
     * @return array<string, mixed>|null
     */
    private function authUser(Request $request): ?array
    {
        $user = $request->user();

        if ($user === null) {
            return null;
        }

        return array_merge($user->toArray(), [
            'avatar_url' => $user->avatar !== null
                ? route('profile.avatar', ['v' => $user->updated_at?->timestamp])
                : $user->avatar_url,
        ]);
    }

    /**
     * Flatten the locale's PHP translation files into "file.key" pairs for the
     * frontend (laravel-vue-i18n format). Delivered on every request so the UI
     * language never depends on build-time generated files.
     *
     * @return array<string, string>
     */
    private function translations(string $locale): array
    {
        $directory = lang_path($locale);

        if (! is_dir($directory)) {
            return [];
        }

        $messages = [];

        foreach (glob($directory.'/*.php') ?: [] as $file) {
            $name = basename($file, '.php');

            // Validation strings are used server-side; keep the payload lean.
            if ($name === 'validation') {
                continue;
            }

            $messages[$name] = require $file;
        }

        return Arr::dot($messages);
    }
}
