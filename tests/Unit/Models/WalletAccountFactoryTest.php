<?php

use App\Models\Account;

test('account factory creates valid tenant and currency relations', function () {
    $account = Account::factory()->create();

    expect($account->tenant)->not->toBeNull();
    expect($account->currency)->not->toBeNull();
});
