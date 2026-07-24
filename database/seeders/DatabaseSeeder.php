<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Tenant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(CurrencySeeder::class);
        $this->call(UserSeeder::class);

        $tenant = Tenant::query()->firstOrCreate([
            'name' => 'Test Tenant',
        ], [
            'locale' => 'pt',
        ]);

        $allCurrencyIds = Currency::query()->orderBy('code')->pluck('id')->all();

        $tenant->locale = 'pt';
        $tenant->save();
        $tenant->syncCurrencyActivations($allCurrencyIds);
        $tenant->ensureCurrencyAssets($allCurrencyIds);
    }
}
