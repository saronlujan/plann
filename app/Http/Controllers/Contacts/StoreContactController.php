<?php

namespace App\Http\Controllers\Contacts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contact\StoreContactRequest;
use App\Models\Contact;
use Illuminate\Http\RedirectResponse;

class StoreContactController extends Controller
{
    public function __invoke(StoreContactRequest $request): RedirectResponse
    {
        Contact::query()->create($request->validated());

        return back();
    }
}
