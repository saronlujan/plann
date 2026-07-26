<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;

class DeleteAccountController extends Controller
{
    public function __invoke(Account $account): RedirectResponse
    {
        $this->authorize('delete', $account);

        if (Transaction::query()->where('account_id', $account->id)->exists()) {
            return back()->withErrors([
                'account' => __('accounts.delete_in_use'),
            ]);
        }

        $account->delete();

        return back();
    }
}
