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

    return User::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa Teste',
        'email' => $email,
        'password' => 'password',
        'locale' => 'pt',
    ]);
}

test('authenticated users may view the dashboard', function () {
    $user = dashboardUser('dashboard@example.com');
    $currency = Currency::query()->firstOrCreate(['code' => 'BRL'], ['name' => 'Real', 'symbol' => 'R$']);

    // A workspace without an account is taken to the guided setup instead.
    Account::create([
        'tenant_id' => $user->tenant_id,
        'currency_id' => $currency->id,
        'name' => 'Conta',
    ]);

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

    $account = Account::create([
        'tenant_id' => $user->tenant_id,
        'currency_id' => $currency->id,
        'name' => 'Conta',
    ]);

    // Accounts start empty: the money already there is an ordinary entry.
    Transaction::query()->create([
        'tenant_id' => $account->tenant_id,
        'account_id' => $account->id,
        'currency_id' => $account->currency_id,
        'movement_type' => 'income',
        'type' => 'unique',
        'effective_date' => '2026-01-01',
        'paid_at' => '2026-01-01',
        'amount' => 500,
        'description' => 'Saldo inicial',
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
            // Opening balance 500 minus the booked 120 expense — not the raw
            // stored opening balance.
            ->where('currencies.0.balance', '380.00')
            ->has('currencies.0.recent', 1));

    CarbonImmutable::setTestNow();
});

test('the dashboard balance matches the balance shown on the accounts page', function () {
    CarbonImmutable::setTestNow('2026-08-15 10:00:00');

    $user = dashboardUser('dash-balance@example.com');
    $currency = Currency::create(['code' => 'BRL', 'name' => 'Real', 'symbol' => 'R$']);

    $checking = Account::create([
        'tenant_id' => $user->tenant_id,
        'currency_id' => $currency->id,
        'name' => 'Conta Corrente',
    ]);

    // Accounts start empty: the money already there is an ordinary entry.
    Transaction::query()->create([
        'tenant_id' => $checking->tenant_id,
        'account_id' => $checking->id,
        'currency_id' => $checking->currency_id,
        'movement_type' => 'income',
        'type' => 'unique',
        'effective_date' => '2026-01-01',
        'paid_at' => '2026-01-01',
        'amount' => 1000,
        'description' => 'Saldo inicial',
    ]);

    $savings = Account::create([
        'tenant_id' => $user->tenant_id,
        'currency_id' => $currency->id,
        'name' => 'Poupanca',
    ]);

    // Accounts start empty: the money already there is an ordinary entry.
    Transaction::query()->create([
        'tenant_id' => $savings->tenant_id,
        'account_id' => $savings->id,
        'currency_id' => $savings->currency_id,
        'movement_type' => 'income',
        'type' => 'unique',
        'effective_date' => '2026-01-01',
        'paid_at' => '2026-01-01',
        'amount' => 250,
        'description' => 'Saldo inicial',
    ]);

    // A credit card is a liability: its spending must not reduce cash on hand.
    $card = Account::create([
        'tenant_id' => $user->tenant_id,
        'currency_id' => $currency->id,
        'name' => 'Cartao',
        'kind' => 'credit_card',
        'credit_limit' => 5000,
        'closing_day' => 20,
        'due_day' => 28,
    ]);

    // Accounts start empty: the money already there is an ordinary entry.
    Transaction::query()->create([
        'tenant_id' => $card->tenant_id,
        'account_id' => $card->id,
        'currency_id' => $card->currency_id,
        'movement_type' => 'income',
        'type' => 'unique',
        'effective_date' => '2026-01-01',
        'paid_at' => '2026-01-01',
        'amount' => 0,
        'description' => 'Saldo inicial',
    ]);

    foreach ([[$checking, 'expense', 300.0], [$checking, 'income', 80.0], [$savings, 'expense', 50.0], [$card, 'expense', 900.0]] as [$account, $movement, $amount]) {
        Transaction::create([
            'tenant_id' => $user->tenant_id,
            'account_id' => $account->id,
            'currency_id' => $currency->id,
            'movement_type' => $movement,
            'type' => 'unique',
            'effective_date' => '2026-08-10',
            'amount' => $amount,
            'adjustment_amount' => 0,
            'description' => 'Movimento',
        ]);
    }

    // (1000 - 300 + 80) + (250 - 50) = 780 + 200 = 980
    actingAs($user)->get('/')->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->where('currencies.0.balance', '980.00'));

    $accountBalances = [];

    actingAs($user)->get(route('accounts'))->assertSuccessful()
        ->assertInertia(function (Assert $page) use (&$accountBalances): void {
            foreach ($page->toArray()['props']['accounts'] as $account) {
                if (isset($account['current_balance'])) {
                    $accountBalances[] = (float) $account['current_balance'];
                }
            }
        });

    expect(array_sum($accountBalances))->toBe(980.0);

    CarbonImmutable::setTestNow();
});

test('the dashboard sends a workspace with no account to the guided setup', function () {
    $user = dashboardUser('dash-empty@example.com');

    // There is nothing to show and nothing the user could do from here.
    actingAs($user)->get('/')->assertRedirect(route('onboarding'));
});

test('the dashboard shows an empty state once an account exists but nothing moved', function () {
    $user = dashboardUser('dash-no-entries@example.com');
    $currency = Currency::query()->firstOrCreate(['code' => 'BRL'], ['name' => 'Real', 'symbol' => 'R$']);

    Account::create([
        'tenant_id' => $user->tenant_id,
        'currency_id' => $currency->id,
        'name' => 'Conta',
    ]);

    actingAs($user)->get('/')->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Dashboard/Index')
            ->where('ready', true));
});
