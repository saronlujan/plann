<?php

namespace App\Http\Controllers\Preferences;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexPreferencesController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $tenant = $request->user()?->tenant()->first();

        abort_if($tenant === null, 403);

        return Inertia::render('Preferences/Index', [
            'locale' => $tenant->locale ?: config('app.locale'),
            'localeOptions' => [
                ['value' => 'pt', 'label' => 'Português'],
                ['value' => 'es', 'label' => 'Español'],
                ['value' => 'en', 'label' => 'English'],
            ],
        ]);
    }
}
