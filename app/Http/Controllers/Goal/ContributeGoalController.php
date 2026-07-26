<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Goal\ContributeGoalRequest;
use App\Models\Goal;
use Illuminate\Http\RedirectResponse;

class ContributeGoalController extends Controller
{
    public function __invoke(ContributeGoalRequest $request, Goal $goal): RedirectResponse
    {
        $goal->increment('current_amount', (float) $request->validated()['amount']);

        return back();
    }
}
