<?php

use App\Models\Account;
use App\Models\Currency;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

/**
 * @return array{0: User, 1: Account, 2: Currency}
 */
function statementFixture(string $email): array
{
    $tenant = Tenant::create(['name' => 'Tenant '.$email]);
    app(TenantContext::class)->setTenantId($tenant->id);

    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa',
        'email' => $email,
        'password' => 'password',
        'locale' => 'pt',
    ]);

    $currency = Currency::query()->firstOrCreate(['code' => 'BRL'], ['name' => 'Real', 'symbol' => 'R$']);
    $account = Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Conta Corrente',
        'balance' => 100,
    ]);

    return [$user, $account, $currency];
}

function statementMovement(Account $account, Currency $currency, string $movement, string $date, float $amount): Transaction
{
    return Transaction::create([
        'tenant_id' => $account->tenant_id,
        'account_id' => $account->id,
        'currency_id' => $currency->id,
        'movement_type' => $movement,
        'type' => 'unique',
        'effective_date' => $date,
        'amount' => $amount,
        'adjustment_amount' => 0,
        'description' => ucfirst($movement),
    ]);
}

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('the accounts list shows the computed current balance', function () {
    CarbonImmutable::setTestNow('2026-08-15 10:00:00');

    [$user, $account, $currency] = statementFixture('acc-list@example.com');
    statementMovement($account, $currency, 'income', '2026-08-05', 200);
    statementMovement($account, $currency, 'expense', '2026-08-10', 50);

    actingAs($user)->get('/accounts')->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Accounts/Index')
            ->has('accounts', 1)
            ->where('accounts.0.current_balance', '250.00')
            ->where('accounts.0.monthly_income', '200.00')
            ->where('accounts.0.monthly_expense', '50.00'));
});

test('the statement shows opening, closing and a running balance', function () {
    CarbonImmutable::setTestNow('2026-08-15 10:00:00');

    [$user, $account, $currency] = statementFixture('acc-statement@example.com');
    statementMovement($account, $currency, 'income', '2026-08-05', 200);
    statementMovement($account, $currency, 'expense', '2026-08-10', 50);

    actingAs($user)->get('/accounts/'.$account->id.'?period=2026-08')->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Accounts/Show')
            ->where('opening', '100.00')
            ->where('income', '200.00')
            ->where('expense', '50.00')
            ->where('closing', '250.00')
            ->has('entries', 2)
            ->where('entries.0.balance', '300.00')
            ->where('entries.1.balance', '250.00'));
});

test('the opening balance carries prior months forward', function () {
    CarbonImmutable::setTestNow('2026-08-15 10:00:00');

    [$user, $account, $currency] = statementFixture('acc-opening@example.com');
    statementMovement($account, $currency, 'income', '2026-07-20', 300); // previous month

    actingAs($user)->get('/accounts/'.$account->id.'?period=2026-08')->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Accounts/Show')
            ->where('opening', '400.00')
            ->has('entries', 0));
});

test('a tenant cannot view another tenant account statement', function () {
    [$victim, $account] = statementFixture('acc-victim@example.com');

    [$attacker] = statementFixture('acc-attacker@example.com');
    app(TenantContext::class)->clear();

    actingAs($attacker)->get('/accounts/'.$account->id)->assertNotFound();
});

test('the accounts page ships the currency symbol for the money fields', function () {
    [$user, , $currency] = statementFixture('acc-symbol@example.com');
    $user->tenant()->firstOrFail()->syncCurrencyActivations([$currency->id]);

    // The modal prefixes balance and credit limit with the symbol, and picks the
    // decimal places off the code (guaraní and peso take none).
    actingAs($user)->get('/accounts')->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->has('currencyOptions', 1)
            ->where('currencyOptions.0.code', 'BRL')
            ->where('currencyOptions.0.symbol', 'R$'));
});
