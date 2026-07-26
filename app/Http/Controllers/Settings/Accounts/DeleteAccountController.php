<?php

namespace App\Http\Controllers\Settings\Accounts;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\RedirectResponse;

class DeleteAccountController extends Controller
{
    public function __invoke(Account $account): RedirectResponse
    {
        $this->authorize('delete', $account);

        $account->delete();

        return back();
    }
}
