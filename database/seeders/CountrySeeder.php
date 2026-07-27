<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Currency;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        // Each country carries the currency a workspace there starts with.
        $countries = [
            ['code' => 'BR', 'name' => 'Brasil', 'currency' => 'BRL'],
            ['code' => 'PY', 'name' => 'Paraguay', 'currency' => 'PYG'],
            ['code' => 'AR', 'name' => 'Argentina', 'currency' => 'ARS'],
        ];

        foreach ($countries as $country) {
            $currency = Currency::query()->whereNull('tenant_id')->where('code', $country['currency'])->first();

            if ($currency === null) {
                continue;
            }

            Country::query()->updateOrCreate(
                ['code' => $country['code']],
                [
                    'name' => $country['name'],
                    'currency_id' => $currency->id,
                    'is_active' => true,
                ],
            );
        }
    }
}
