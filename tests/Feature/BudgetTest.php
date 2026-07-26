<?php

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

/**
 * @return array{0: User, 1: Currency, 2: Category}
 */
function budgetFixture(string $email): array
{
    $tenant = Tenant::create(['name' => 'Tenant '.$email]);
    app(TenantContext::class)->setTenantId($tenant->id);

    $user = User::create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa',
        'email' => $email,
        'password' => 'password',
        'locale' => 'pt',
    ]);

    $currency = Currency::query()->firstOrCreate(['code' => 'BRL'], ['name' => 'Real', 'symbol' => 'R$']);
    $tenant->syncCurrencyActivations([$currency->id]);

    $category = Category::create([
        'tenant_id' => $tenant->id,
        'name' => 'Mercado',
        'type' => 'expense',
        'color' => 'green',
    ]);

    return [$user, $currency, $category];
}

test('users may create a budget', function () {
    [$user, $currency, $category] = budgetFixture('budget-create@example.com');

    actingAs($user)
        ->post('/budgets', [
            'category_id' => $category->id,
            'currency_id' => $currency->id,
            'amount' => 800,
        ])
        ->assertRedirect();

    expect(Budget::query()->where('category_id', $category->id)->exists())->toBeTrue();
});

test('a dual-use (both) category can be budgeted', function () {
    [$user, $currency] = budgetFixture('budget-both@example.com');
    $both = Category::create([
        'tenant_id' => $user->tenant_id,
        'name' => 'Hospedagem',
        'type' => 'both',
        'color' => 'blue',
    ]);

    actingAs($user)
        ->post('/budgets', ['category_id' => $both->id, 'currency_id' => $currency->id, 'amount' => 600])
        ->assertRedirect();

    expect(Budget::query()->where('category_id', $both->id)->exists())->toBeTrue();
});

test('a budget requires an expense category', function () {
    [$user, $currency] = budgetFixture('budget-income@example.com');
    $income = Category::create([
        'tenant_id' => $user->tenant_id,
        'name' => 'Salário',
        'type' => 'income',
        'color' => 'blue',
    ]);

    actingAs($user)
        ->post('/budgets', ['category_id' => $income->id, 'currency_id' => $currency->id, 'amount' => 500])
        ->assertSessionHasErrors('category_id');
});

test('a category cannot be budgeted twice in the same currency', function () {
    [$user, $currency, $category] = budgetFixture('budget-dup@example.com');
    Budget::create([
        'tenant_id' => $user->tenant_id,
        'category_id' => $category->id,
        'currency_id' => $currency->id,
        'amount' => 500,
    ]);

    actingAs($user)
        ->post('/budgets', ['category_id' => $category->id, 'currency_id' => $currency->id, 'amount' => 700])
        ->assertSessionHasErrors('category_id');
});

test('the index computes the amount spent this month', function () {
    CarbonImmutable::setTestNow('2026-08-15 10:00:00');

    [$user, $currency, $category] = budgetFixture('budget-spent@example.com');
    $account = Account::create([
        'tenant_id' => $user->tenant_id,
        'currency_id' => $currency->id,
        'name' => 'Conta',
        'balance' => 0,
    ]);
    Budget::create([
        'tenant_id' => $user->tenant_id,
        'category_id' => $category->id,
        'currency_id' => $currency->id,
        'amount' => 800,
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

    actingAs($user)->get('/budgets')->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Budgets/Index')
            ->has('budgets', 1)
            ->where('budgets.0.spent', '120.00')
            ->where('budgets.0.amount', '800.00'));

    CarbonImmutable::setTestNow();
});

test('users may update and delete a budget', function () {
    [$user, $currency, $category] = budgetFixture('budget-ud@example.com');
    $budget = Budget::create([
        'tenant_id' => $user->tenant_id,
        'category_id' => $category->id,
        'currency_id' => $currency->id,
        'amount' => 500,
    ]);

    actingAs($user)
        ->patch('/budgets/'.$budget->id, ['category_id' => $category->id, 'currency_id' => $currency->id, 'amount' => 900])
        ->assertRedirect();
    expect($budget->fresh()?->amount)->toBe('900.00');

    actingAs($user)->delete('/budgets/'.$budget->id)->assertRedirect();
    expect(Budget::query()->whereKey($budget->id)->exists())->toBeFalse();
});

test('a tenant cannot delete another tenant budget', function () {
    [$victim, $currency, $category] = budgetFixture('budget-victim@example.com');
    $budget = Budget::create([
        'tenant_id' => $victim->tenant_id,
        'category_id' => $category->id,
        'currency_id' => $currency->id,
        'amount' => 500,
    ]);

    [$attacker] = budgetFixture('budget-attacker@example.com');
    app(TenantContext::class)->clear();

    actingAs($attacker)->delete('/budgets/'.$budget->id)->assertNotFound();
    expect(Budget::withoutGlobalScopes()->whereKey($budget->id)->exists())->toBeTrue();
});
