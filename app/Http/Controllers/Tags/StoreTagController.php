<?php

namespace App\Http\Controllers\Tags;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tag\StoreTagRequest;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;

class StoreTagController extends Controller
{
    public function __invoke(StoreTagRequest $request): RedirectResponse
    {
        Tag::query()->create($request->validated());

        return back();
    }
}
