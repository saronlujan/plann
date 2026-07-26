<?php

namespace App\Http\Controllers\Settings\Accounts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdateAccountRequest;
use App\Models\Account;
use Illuminate\Http\RedirectResponse;

class UpdateAccountController extends Controller
{
    public function __invoke(UpdateAccountRequest $request, Account $account): RedirectResponse
    {
        $this->authorize('update', $account);

        $account->update($request->validated());

        return back();
    }
}
