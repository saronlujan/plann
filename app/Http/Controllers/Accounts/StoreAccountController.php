<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\StoreAccountRequest;
use App\Models\Account;
use Illuminate\Http\RedirectResponse;

class StoreAccountController extends Controller
{
    public function __invoke(StoreAccountRequest $request): RedirectResponse
    {
        Account::query()->create($request->validated());

        return back();
    }
}
