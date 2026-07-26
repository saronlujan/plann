<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;

class AccountPolicy
{
    public function update(User $user, Account $account): bool
    {
        return $user->tenant_id === $account->tenant_id;
    }

    public function delete(User $user, Account $account): bool
    {
        return $user->tenant_id === $account->tenant_id;
    }
}
