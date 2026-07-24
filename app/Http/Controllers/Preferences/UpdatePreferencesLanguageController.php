<?php

namespace App\Http\Controllers\Preferences;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UpdatePreferencesLanguageController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'in:pt,es,en'],
        ]);

        $tenant = $request->user()?->tenant()->firstOrFail();
        $tenant->update([
            'locale' => $validated['locale'],
        ]);

        app()->setLocale($validated['locale']);

        return back()->with('success', 'Idioma atualizado com sucesso.');
    }
}
