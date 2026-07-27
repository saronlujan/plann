<?php

namespace App\Support\Registration;

use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Picks which country the signup form should start on.
 *
 * Alphabetical order is a poor default: it puts Argentina in front of every
 * Brazilian visitor. The browser usually knows better, so ask it first.
 */
class ResolveDefaultCountry
{
    /**
     * Language to country, used when the browser sends no region.
     *
     * Spanish is ambiguous between the two Spanish-speaking countries served;
     * Argentina is the larger market, so it wins the tie.
     */
    private const LANGUAGE_FALLBACK = [
        'pt' => 'BR',
        'es' => 'AR',
        'en' => 'BR',
    ];

    /**
     * @param  Collection<int, Country>  $countries
     */
    public function handle(Request $request, Collection $countries): ?string
    {
        $available = $countries->pluck('code')->all();

        if ($available === []) {
            return null;
        }

        // "pt_BR" and "es_PY" carry the region outright — the strongest signal,
        // and it separates Paraguay from Argentina where the language cannot.
        foreach ($request->getLanguages() as $language) {
            $region = mb_strtoupper((string) (explode('_', $language)[1] ?? ''));

            if ($region !== '' && in_array($region, $available, true)) {
                return $region;
            }
        }

        // No region: fall back on the language, then on the app's own locale for
        // a visitor who switched it manually.
        foreach ($request->getLanguages() as $language) {
            $code = self::LANGUAGE_FALLBACK[mb_strtolower(explode('_', $language)[0])] ?? null;

            if ($code !== null && in_array($code, $available, true)) {
                return $code;
            }
        }

        $localeCode = self::LANGUAGE_FALLBACK[app()->getLocale()] ?? null;

        if ($localeCode !== null && in_array($localeCode, $available, true)) {
            return $localeCode;
        }

        return $available[0];
    }
}
