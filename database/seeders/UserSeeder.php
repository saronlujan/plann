<?php

namespace Database\Seeders;

use App\Enums\PlanSlug;
use App\Models\Currency;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $currencies = Currency::query()->get()->keyBy('code');

        $tenant = Tenant::query()->firstOrCreate(
            ['name' => 'Test Tenant'],
            [
                'plan_slug' => PlanSlug::Basic->value,
                'trial_ends_at' => now()->addDays(14),
            ],
        );

        app(TenantContext::class)->setTenantId($tenant->id);

        // Activate every available currency for the test workspace.
        $tenant->syncCurrencyActivations($currencies->pluck('id')->all());

        // One account per currency, plus a second BRL account to exercise transfers.
        $accounts = [
            ['name' => 'Conta Corrente', 'code' => 'BRL', 'balance' => 5000],
            ['name' => 'Conta Poupança', 'code' => 'BRL', 'balance' => 12000],
            ['name' => 'Conta Dólar', 'code' => 'USD', 'balance' => 800],
            ['name' => 'Conta Peso', 'code' => 'ARS', 'balance' => 150000],
            ['name' => 'Conta Guarani', 'code' => 'PYG', 'balance' => 2500000],
            ['name' => 'Carteira Tether', 'code' => 'USDT', 'balance' => 300],
        ];

        foreach ($accounts as $account) {
            $tenant->accounts()->updateOrCreate(
                ['name' => $account['name']],
                [
                    'currency_id' => $currencies[$account['code']]->id,
                    'balance' => $account['balance'],
                ],
            );
        }

        $user = User::query()->updateOrCreate(
            [
                'email' => 'saronlujan@gmail.com',
            ],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Saron Lujan',
                'password' => '12345678',
                'avatar_url' => 'https://avatars.githubusercontent.com/u/7363056?v=4',
                'locale' => 'pt',
            ],
        );

        // The seeded account skips the verification step so `migrate:fresh --seed`
        // lands straight on a usable app.
        $user->markEmailAsVerified();
    }
}
