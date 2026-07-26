<?php

namespace App\Http\Controllers\Budget;

use App\Http\Controllers\Controller;
use App\Http\Requests\Budget\StoreBudgetRequest;
use App\Models\Budget;
use Illuminate\Http\RedirectResponse;

class StoreBudgetController extends Controller
{
    public function __invoke(StoreBudgetRequest $request): RedirectResponse
    {
        Budget::query()->create($request->validated());

        return back();
    }
}
