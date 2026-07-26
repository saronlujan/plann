<?php

namespace App\Http\Controllers\Settings\Tags;

use App\Enums\LabelColor;
use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexTagsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Settings/Tags', [
            'tags' => Tag::query()
                ->orderBy('name')
                ->get(['id', 'name', 'color'])
                ->map(fn (Tag $tag): array => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'color' => $tag->color->value,
                ])
                ->all(),
            'colorOptions' => LabelColor::options(),
        ]);
    }
}
