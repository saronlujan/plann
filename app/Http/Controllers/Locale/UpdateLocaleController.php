<?php

namespace App\Http\Controllers\Locale;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Lets guests (login, register, password reset) switch the interface language.
 * The choice is kept in the session; authenticated users have their own
 * per-user locale applied by EnsureTenantContext instead.
 */
class UpdateLocaleController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', Rule::in(['pt', 'es', 'en'])],
        ]);

        $request->session()->put('locale', $validated['locale']);

        return back();
    }
}
