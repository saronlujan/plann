<?php

namespace App\Http\Controllers\Categories;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;

class StoreCategoryController extends Controller
{
    public function __invoke(StoreCategoryRequest $request): RedirectResponse
    {
        Category::query()->create($request->validated());

        return back();
    }
}
