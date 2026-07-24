<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $currency = Currency::query()->firstOrCreate([
            'code' => 'BRL',
        ], [
            'name' => 'Brazilian Real',
            'symbol' => 'R$',
        ]);

        $tenant = Tenant::query()->firstOrCreate([
            'name' => 'Test Tenant',
        ], [
            'locale' => 'pt',
        ]);

        $tenant->locale = 'pt';
        $tenant->save();

        $tenant->syncCurrencyActivations([$currency->id]);
        $tenant->ensureCurrencyAssets([$currency->id]);

        User::query()->updateOrCreate(
            [
                'email' => 'saronlujan@gmail.com',
            ],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Saron Lujan',
                'password' => '12345678',
            ],
        );
    }
}
