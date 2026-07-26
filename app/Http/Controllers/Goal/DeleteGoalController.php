<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Models\Goal;
use Illuminate\Http\RedirectResponse;

class DeleteGoalController extends Controller
{
    public function __invoke(Goal $goal): RedirectResponse
    {
        $goal->delete();

        return back();
    }
}
