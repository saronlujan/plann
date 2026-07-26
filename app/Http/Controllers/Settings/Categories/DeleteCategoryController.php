<?php

namespace App\Http\Controllers\Settings\Categories;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;

class DeleteCategoryController extends Controller
{
    public function __invoke(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        $category->delete();

        return back();
    }
}
