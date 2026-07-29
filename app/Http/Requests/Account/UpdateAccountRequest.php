<?php

namespace App\Http\Requests\Account;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Contracts\Validation\Validator;

class UpdateAccountRequest extends StoreAccountRequest
{
    /**
     * The currency is settled once money has moved through the account.
     *
     * Every transaction stores the currency it was recorded in, so switching the
     * account's would leave a statement of amounts that were never in it — and no
     * rate can honestly convert an entry after the fact. The transactions have to
     * be dealt with first.
     */
    public function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);

        $validator->after(function (Validator $validator): void {
            $account = $this->route('account');
            $currencyId = $this->integer('currency_id');

            if (! $account instanceof Account || $currencyId === 0) {
                return;
            }

            if ($currencyId === $account->currency_id) {
                return;
            }

            if (! Transaction::query()->where('account_id', $account->id)->exists()) {
                return;
            }

            $validator->errors()->add('currency_id', __('accounts.errors.currency_locked'));
        });
    }
}
