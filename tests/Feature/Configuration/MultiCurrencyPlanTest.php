<?php

use App\Enums\PlanSlug;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\PlanSeeder;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

/**
 * Holding more than one currency is the Pro feature, and opening an account is
 * the only way to start holding one — so this is where the plan is enforced.
 *
 * @return array{0: User, 1: Tenant}
 */
function planFixture(string $email, PlanSlug $plan): array
{
    $tenant = Tenant::create(['name' => 'Tenant '.$email, 'plan_slug' => $plan->value]);

    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa',
        'email' => $email,
        'password' => 'password',
    ]);

    app(TenantContext::class)->setTenantId($tenant->id);

    return [$user, $tenant];
}

function makeCurrency(string $code): Currency
{
    return Currency::query()->firstOrCreate(['code' => $code], ['name' => $code, 'symbol' => '$']);
}

test('basic may open an account in its first currency', function () {
    [$user] = planFixture('plan-basic-first@example.com', PlanSlug::Basic);

    actingAs($user)
        ->post('/accounts', [
            'name' => 'Conta',
            'kind' => 'account',
            'currency_id' => makeCurrency('BRL')->id,
        ])
        ->assertSessionHasNoErrors();

    expect(Account::query()->count())->toBe(1);
});

test('basic is refused an account in a second currency', function () {
    [$user, $tenant] = planFixture('plan-basic-second@example.com', PlanSlug::Basic);

    Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => makeCurrency('BRL')->id,
        'name' => 'Conta BRL',
    ]);

    actingAs($user)
        ->post('/accounts', [
            'name' => 'Conta USD',
            'kind' => 'account',
            'currency_id' => makeCurrency('USD')->id,
        ])
        ->assertSessionHasErrors('currency_id');

    expect(Account::query()->count())->toBe(1);
});

test('basic may keep adding accounts in the currency it already uses', function () {
    [$user, $tenant] = planFixture('plan-basic-same@example.com', PlanSlug::Basic);

    $brl = makeCurrency('BRL');

    Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $brl->id,
        'name' => 'Conta Corrente',
    ]);

    // The plan limits currencies, never accounts.
    actingAs($user)
        ->post('/accounts', [
            'name' => 'Conta Poupança',
            'kind' => 'account',
            'currency_id' => $brl->id,
        ])
        ->assertSessionHasNoErrors();

    expect(Account::query()->count())->toBe(2);
});

test('pro may open accounts in several currencies', function () {
    [$user, $tenant] = planFixture('plan-pro@example.com', PlanSlug::Pro);

    Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => makeCurrency('BRL')->id,
        'name' => 'Conta BRL',
    ]);

    actingAs($user)
        ->post('/accounts', [
            'name' => 'Conta USD',
            'kind' => 'account',
            'currency_id' => makeCurrency('USD')->id,
        ])
        ->assertSessionHasNoErrors();

    expect($tenant->activeCurrencies()->get()->count())->toBe(2);
});

test('a downgrade never strips currencies the workspace already uses', function () {
    [, $tenant] = planFixture('plan-downgrade@example.com', PlanSlug::Pro);

    foreach (['BRL', 'USD'] as $code) {
        Account::create([
            'tenant_id' => $tenant->id,
            'currency_id' => makeCurrency($code)->id,
            'name' => 'Conta '.$code,
        ]);
    }

    $tenant->update(['plan_slug' => PlanSlug::Basic->value]);

    // Taking currencies away would hide accounts and transactions the user owns.
    expect($tenant->activeCurrencies()->get()->count())->toBe(2);
});

test('the refusal carries a message the form can show', function () {
    [$user, $tenant] = planFixture('plan-message@example.com', PlanSlug::Basic);

    Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => makeCurrency('BRL')->id,
        'name' => 'Conta BRL',
    ]);

    actingAs($user)
        ->post('/accounts', [
            'name' => 'Conta USD',
            'kind' => 'account',
            'currency_id' => makeCurrency('USD')->id,
        ])
        ->assertSessionHasErrors(['currency_id' => __('accounts.errors.plan_limit')]);
});

test('a currency starts being used the moment an account is opened in it', function () {
    [$user, $tenant] = planFixture('currency-in-use@example.com', PlanSlug::Pro);

    $usd = makeCurrency('USD');

    // Nothing is "activated" anywhere: the account is the record.
    expect($tenant->activeCurrencies()->get()->count())->toBe(0);

    actingAs($user)->post('/accounts', [
        'name' => 'Conta USD',
        'kind' => 'account',
        'currency_id' => $usd->id,
    ])->assertSessionHasNoErrors();

    expect($tenant->activeCurrencies()->pluck('currencies.id')->all())->toBe([$usd->id]);
});

test('a currency stops being used when its last account goes', function () {
    [$user, $tenant] = planFixture('currency-out-of-use@example.com', PlanSlug::Pro);

    $usd = makeCurrency('USD');

    actingAs($user)->post('/accounts', [
        'name' => 'Conta USD',
        'kind' => 'account',
        'currency_id' => $usd->id,
    ]);

    $account = Account::query()->firstOrFail();

    actingAs($user)->delete('/accounts/'.$account->id)->assertSessionHasNoErrors();

    // Derived, so it needs no cleanup of its own — which is the point.
    expect($tenant->activeCurrencies()->get()->count())->toBe(0);
});

test('two accounts in one currency count it once', function () {
    [, $tenant] = planFixture('currency-distinct@example.com', PlanSlug::Pro);

    $brl = makeCurrency('BRL');

    foreach (['Corrente', 'Poupança'] as $name) {
        Account::create([
            'tenant_id' => $tenant->id,
            'currency_id' => $brl->id,
            'name' => 'Conta '.$name,
        ]);
    }

    // Counted on the collection, not the query: a distinct row select does not
    // reach count(*), which would see one row per account.
    expect($tenant->activeCurrencies()->get()->count())->toBe(1);
});

test('signup records the chosen currency on the workspace', function () {
    app(CurrencySeeder::class)->run();
    app(CountrySeeder::class)->run();
    app(PlanSeeder::class)->run();

    // The form asks for it, so it has to land somewhere: without this the answer
    // was validated and thrown away.
    post('/register', [
        'name' => 'Pessoa',
        'email' => 'signup-currency@example.com',
        'country_code' => 'BR',
        'currency_code' => 'USD',
        'plan_slug' => 'basic',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $tenant = Tenant::query()->where('name', 'Pessoa')->firstOrFail();

    expect($tenant->currency_id)->toBe(Currency::query()->where('code', 'USD')->value('id'));
});

test('basic is offered only the currency it signed up with', function () {
    [$user, $tenant] = planFixture('basic-single-option@example.com', PlanSlug::Basic);

    $brl = makeCurrency('BRL');
    makeCurrency('USD');
    $tenant->update(['currency_id' => $brl->id]);

    // One option means the form hides the field: the workspace cannot lock itself
    // into a currency it never chose.
    actingAs($user)->get('/accounts')->assertSuccessful()
        ->assertInertia(function ($page) use ($brl): void {
            $options = $page->toArray()['props']['currencyOptions'];

            expect($options)->toHaveCount(1);
            expect($options[0]['value'])->toBe((string) $brl->id);
        });
});

test('basic is refused a first account outside its signup currency', function () {
    [$user, $tenant] = planFixture('basic-wrong-currency@example.com', PlanSlug::Basic);

    $tenant->update(['currency_id' => makeCurrency('BRL')->id]);

    // Enforced on the server too: the short option list is convenience, not the
    // rule.
    actingAs($user)
        ->post('/accounts', [
            'name' => 'Conta USD',
            'kind' => 'account',
            'currency_id' => makeCurrency('USD')->id,
        ])
        ->assertSessionHasErrors('currency_id');
});

test('pro is offered the whole catalogue', function () {
    [$user, $tenant] = planFixture('pro-catalogue@example.com', PlanSlug::Pro);

    $tenant->update(['currency_id' => makeCurrency('BRL')->id]);
    makeCurrency('USD');
    makeCurrency('PYG');

    actingAs($user)->get('/accounts')->assertSuccessful()
        ->assertInertia(function ($page): void {
            expect(count($page->toArray()['props']['currencyOptions']))->toBe(3);
        });
});

/**
 * @return array{0: User, 1: Account, 2: Currency}
 */
function accountWithEntry(string $email): array
{
    [$user, $tenant] = planFixture($email, PlanSlug::Pro);

    $brl = makeCurrency('BRL');

    $account = Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $brl->id,
        'name' => 'Conta',
    ]);

    Transaction::query()->create([
        'tenant_id' => $tenant->id,
        'account_id' => $account->id,
        'currency_id' => $brl->id,
        'movement_type' => 'expense',
        'type' => 'unique',
        'effective_date' => '2026-07-10',
        'amount' => 50,
        'description' => 'Compra',
    ]);

    return [$user, $account, $brl];
}

test('the currency is settled once the account has entries', function () {
    [$user, $account, $brl] = accountWithEntry('currency-locked@example.com');

    // Every entry stores the currency it was recorded in, so switching the
    // account's would leave a statement of amounts that were never in it.
    actingAs($user)
        ->patch('/accounts/'.$account->id, [
            'name' => 'Conta',
            'kind' => 'account',
            'currency_id' => makeCurrency('USD')->id,
        ])
        ->assertSessionHasErrors('currency_id');

    expect($account->refresh()->currency_id)->toBe($brl->id);
});

test('the lock leaves the rest of the account editable', function () {
    [$user, $account, $brl] = accountWithEntry('currency-locked-rename@example.com');

    // Resending the currency it already has must not turn a rename into an error.
    actingAs($user)
        ->patch('/accounts/'.$account->id, [
            'name' => 'Conta Nova',
            'kind' => 'account',
            'currency_id' => $brl->id,
        ])
        ->assertSessionHasNoErrors();

    expect($account->refresh()->name)->toBe('Conta Nova');
});

test('an empty account may still change currency', function () {
    [$user, $tenant] = planFixture('currency-unlocked@example.com', PlanSlug::Pro);

    $account = Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => makeCurrency('BRL')->id,
        'name' => 'Conta',
    ]);

    // Nothing has been recorded yet, so there is nothing to contradict.
    $usd = makeCurrency('USD');

    actingAs($user)
        ->patch('/accounts/'.$account->id, [
            'name' => 'Conta',
            'kind' => 'account',
            'currency_id' => $usd->id,
        ])
        ->assertSessionHasNoErrors();

    expect($account->refresh()->currency_id)->toBe($usd->id);
});

test('the accounts page says which accounts are locked', function () {
    [$user, $account] = accountWithEntry('currency-locked-page@example.com');

    // The modal disables the field from this, so the user is not offered a
    // choice the server will refuse.
    actingAs($user)->get('/accounts')->assertSuccessful()
        ->assertInertia(function (AssertableInertia $page) use ($account): void {
            $accounts = collect($page->toArray()['props']['accounts']);

            expect($accounts->firstWhere('id', $account->id)['has_transactions'])->toBeTrue();
        });
});
