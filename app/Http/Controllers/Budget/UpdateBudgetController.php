<?php

namespace App\Http\Controllers\Budget;

use App\Http\Controllers\Controller;
use App\Http\Requests\Budget\UpdateBudgetRequest;
use App\Models\Budget;
use Illuminate\Http\RedirectResponse;

class UpdateBudgetController extends Controller
{
    public function __invoke(UpdateBudgetRequest $request, Budget $budget): RedirectResponse
    {
        $budget->update($request->validated());

        return back();
    }
}
