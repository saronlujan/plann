<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

function dashboardUser(string $email): User
{
    $tenant = Tenant::create(['name' => 'Tenant '.$email]);
    app(TenantContext::class)->setTenantId($tenant->id);

    return User::create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa Teste',
        'email' => $email,
        'password' => 'password',
        'locale' => 'pt',
    ]);
}

test('authenticated users may view the dashboard', function () {
    $user = dashboardUser('dashboard@example.com');

    actingAs($user)
        ->get('/')
        ->assertSuccessful()
        ->assertInertia(function (Assert $page): void {
            $page->component('Dashboard/Index')
                ->where('auth.user.name', 'Pessoa Teste');
        });
});

test('the dashboard renders an overview for the primary currency', function () {
    CarbonImmutable::setTestNow('2026-08-15 10:00:00');

    $user = dashboardUser('dash@example.com');
    $currency = Currency::create(['code' => 'BRL', 'name' => 'Real', 'symbol' => 'R$']);
    $user->tenant()->first()->syncCurrencyActivations([$currency->id]);

    $account = Account::create([
        'tenant_id' => $user->tenant_id,
        'currency_id' => $currency->id,
        'name' => 'Conta',
        'balance' => 500,
    ]);
    $category = Category::create([
        'tenant_id' => $user->tenant_id,
        'name' => 'Mercado',
        'type' => 'expense',
        'color' => 'green',
    ]);

    Transaction::create([
        'tenant_id' => $user->tenant_id,
        'account_id' => $account->id,
        'currency_id' => $currency->id,
        'category_id' => $category->id,
        'movement_type' => 'expense',
        'type' => 'unique',
        'effective_date' => '2026-08-10',
        'amount' => 120,
        'adjustment_amount' => 0,
        'description' => 'Compras',
    ]);

    actingAs($user)->get('/')->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Dashboard/Index')
            ->where('ready', true)
            ->has('currencies', 1)
            ->where('currencies.0.code', 'BRL')
            ->has('currencies.0.series', 6)
            ->has('currencies.0.expensesByCategory', 1)
            ->where('currencies.0.expensesByCategory.0.name', 'Mercado')
            ->where('currencies.0.monthlyExpenses', '120.00')
            ->has('currencies.0.recent', 1));

    CarbonImmutable::setTestNow();
});

test('the dashboard shows an empty state without an active currency', function () {
    $user = dashboardUser('dash-empty@example.com');

    actingAs($user)->get('/')->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Dashboard/Index')
            ->where('ready', false));
});
