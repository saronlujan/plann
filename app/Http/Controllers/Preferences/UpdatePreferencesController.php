<?php

namespace App\Http\Controllers\Preferences;

use App\Actions\Preferences\UpdatePreferences;
use App\Http\Controllers\Controller;
use App\Http\Requests\Preferences\UpdatePreferencesRequest;
use Illuminate\Http\RedirectResponse;

class UpdatePreferencesController extends Controller
{
    public function __invoke(UpdatePreferencesRequest $request, UpdatePreferences $updatePreferences): RedirectResponse
    {
        $updatePreferences->handle($request->user(), $request->validated());

        return back();
    }
}
