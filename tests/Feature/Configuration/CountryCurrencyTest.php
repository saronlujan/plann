<?php

use App\Models\Currency;
use Database\Seeders\CurrencySeeder;

test('currency seeds are created for the mvp currencies', function () {
    app(CurrencySeeder::class)->run();

    $currencyCodes = Currency::query()->pluck('code')->all();

    sort($currencyCodes);

    expect($currencyCodes)->toBe(['ARS', 'BRL', 'PYG', 'USD', 'USDT']);
});
