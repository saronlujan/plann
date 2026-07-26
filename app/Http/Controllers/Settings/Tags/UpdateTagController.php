<?php

namespace App\Http\Controllers\Settings\Tags;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tag\UpdateTagRequest;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;

class UpdateTagController extends Controller
{
    public function __invoke(UpdateTagRequest $request, Tag $tag): RedirectResponse
    {
        $this->authorize('update', $tag);

        $tag->update($request->validated());

        return back();
    }
}
