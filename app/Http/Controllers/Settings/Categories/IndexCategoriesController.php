<?php

namespace App\Http\Controllers\Settings\Categories;

use App\Enums\CategoryType;
use App\Enums\LabelColor;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexCategoriesController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Settings/Categories', [
            'categories' => Category::query()
                ->orderBy('type')
                ->orderBy('name')
                ->get(['id', 'name', 'type', 'color'])
                ->map(fn (Category $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'type' => $category->type->value,
                    'color' => $category->color->value,
                ])
                ->all(),
            'categoryTypeOptions' => array_map(
                fn (CategoryType $type): array => ['value' => $type->value, 'label' => $type->label()],
                CategoryType::cases(),
            ),
            'colorOptions' => LabelColor::options(),
        ]);
    }
}
