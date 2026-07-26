<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Goal\UpdateGoalRequest;
use App\Models\Goal;
use Illuminate\Http\RedirectResponse;

class UpdateGoalController extends Controller
{
    public function __invoke(UpdateGoalRequest $request, Goal $goal): RedirectResponse
    {
        $goal->update($request->validated());

        return back();
    }
}
