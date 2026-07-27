<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['code' => 'BRL', 'name' => 'Brazilian Real', 'symbol' => 'R$'],
            ['code' => 'ARS', 'name' => 'Argentine Peso', 'symbol' => '$'],
            ['code' => 'PYG', 'name' => 'Paraguayan Guarani', 'symbol' => '₲'],
            ['code' => 'USD', 'name' => 'United States Dollar', 'symbol' => '$'],
            ['code' => 'USDT', 'name' => 'Tether', 'symbol' => '₮'],
        ];

        foreach ($currencies as $currencyData) {
            Currency::query()->updateOrCreate(
                ['code' => $currencyData['code']],
                $currencyData,
            );
        }
    }
}
