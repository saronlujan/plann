<?php

namespace App\Http\Controllers\Budget;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use Illuminate\Http\RedirectResponse;

class DeleteBudgetController extends Controller
{
    public function __invoke(Budget $budget): RedirectResponse
    {
        $budget->delete();

        return back();
    }
}
