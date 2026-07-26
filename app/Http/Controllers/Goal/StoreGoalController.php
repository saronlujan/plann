<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Goal\StoreGoalRequest;
use App\Models\Goal;
use Illuminate\Http\RedirectResponse;

class StoreGoalController extends Controller
{
    public function __invoke(StoreGoalRequest $request): RedirectResponse
    {
        Goal::query()->create($request->validated());

        return back();
    }
}
