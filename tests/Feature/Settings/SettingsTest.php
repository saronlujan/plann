<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Tag;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

function activateCurrency(User $user, string $code = 'BRL'): Currency
{
    $currency = Currency::query()->firstOrCreate(['code' => $code], ['name' => $code, 'symbol' => '$']);
    $user->tenant()->first()->syncCurrencyActivations([$currency->id]);

    return $currency;
}

function settingsUser(string $email): User
{
    $tenant = Tenant::create(['name' => 'Tenant '.$email]);
    app(TenantContext::class)->setTenantId($tenant->id);

    return User::create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa',
        'email' => $email,
        'password' => 'password',
        'locale' => 'pt',
    ]);
}

test('each module has its own focused page', function () {
    $user = settingsUser('settings@example.com');
    Category::create(['tenant_id' => $user->tenant_id, 'name' => 'Salário', 'type' => 'income']);
    Tag::create(['tenant_id' => $user->tenant_id, 'name' => 'fixo']);

    actingAs($user)->get('/categories')->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Categories/Index')
            ->has('categories', 1)
            ->has('categoryTypeOptions', 3));

    actingAs($user)->get('/tags')->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Tags/Index')
            ->has('tags', 1));

    actingAs($user)->get('/currencies')->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Currencies/Index')
            ->has('currencies'));
});

test('users may create a category', function () {
    $user = settingsUser('cat-create@example.com');

    actingAs($user)
        ->post('/categories', ['name' => 'Mercado', 'type' => 'expense', 'color' => 'green'])
        ->assertRedirect();

    $category = Category::query()->where('name', 'Mercado')->where('type', 'expense')->first();
    expect($category)->not->toBeNull();
    expect($category?->color->value)->toBe('green');
});

test('a category may be dual-use (both)', function () {
    $user = settingsUser('cat-both@example.com');

    actingAs($user)
        ->post('/categories', ['name' => 'Hospedagem', 'type' => 'both', 'color' => 'blue'])
        ->assertRedirect();

    expect(Category::query()->where('name', 'Hospedagem')->where('type', 'both')->exists())->toBeTrue();
});

test('duplicate category name and type is rejected', function () {
    $user = settingsUser('cat-dup@example.com');
    Category::create(['tenant_id' => $user->tenant_id, 'name' => 'Mercado', 'type' => 'expense']);

    actingAs($user)
        ->post('/categories', ['name' => 'Mercado', 'type' => 'expense', 'color' => 'red'])
        ->assertSessionHasErrors('name');

    // Same name but different type is allowed.
    actingAs($user)
        ->post('/categories', ['name' => 'Mercado', 'type' => 'income', 'color' => 'red'])
        ->assertSessionDoesntHaveErrors();
});

test('users may update and delete a category', function () {
    $user = settingsUser('cat-ud@example.com');
    $category = Category::create(['tenant_id' => $user->tenant_id, 'name' => 'Mercado', 'type' => 'expense']);

    actingAs($user)
        ->patch('/categories/'.$category->id, ['name' => 'Feira', 'type' => 'expense', 'color' => 'blue'])
        ->assertRedirect();

    expect($category->fresh()?->name)->toBe('Feira');

    actingAs($user)
        ->delete('/categories/'.$category->id)
        ->assertRedirect();

    expect(Category::query()->whereKey($category->id)->exists())->toBeFalse();
});

test('users may create and delete a tag', function () {
    $user = settingsUser('tag-cd@example.com');

    actingAs($user)->post('/tags', ['name' => 'urgente', 'color' => 'purple'])->assertRedirect();

    $tag = Tag::query()->where('name', 'urgente')->first();
    expect($tag)->not->toBeNull();

    actingAs($user)->delete('/tags/'.$tag->id)->assertRedirect();
    expect(Tag::query()->whereKey($tag->id)->exists())->toBeFalse();
});

test('a tenant cannot modify another tenant category', function () {
    $victim = settingsUser('victim-cat@example.com');
    $victimCategory = Category::create(['tenant_id' => $victim->tenant_id, 'name' => 'Privada', 'type' => 'income']);

    $attacker = settingsUser('attacker-cat@example.com');
    app(TenantContext::class)->clear();

    actingAs($attacker)
        ->patch('/categories/'.$victimCategory->id, ['name' => 'Hack', 'type' => 'income'])
        ->assertNotFound();

    actingAs($attacker)
        ->delete('/categories/'.$victimCategory->id)
        ->assertNotFound();

    expect($victimCategory->fresh()?->name)->toBe('Privada');
});

test('users may create, update and delete an account', function () {
    $user = settingsUser('acc-crud@example.com');
    $currency = activateCurrency($user, 'BRL');

    actingAs($user)
        ->post('/accounts', ['name' => 'Conta A', 'currency_id' => $currency->id, 'balance' => 100.5])
        ->assertRedirect();

    $account = Account::query()->where('name', 'Conta A')->first();
    expect($account)->not->toBeNull();
    expect($account?->balance)->toBe('100.50');

    actingAs($user)
        ->patch('/accounts/'.$account->id, ['name' => 'Conta B', 'currency_id' => $currency->id, 'balance' => 0])
        ->assertRedirect();

    expect($account->fresh()?->name)->toBe('Conta B');

    actingAs($user)->delete('/accounts/'.$account->id)->assertRedirect();
    expect(Account::query()->whereKey($account->id)->exists())->toBeFalse();
});

test('an account with transactions cannot be deleted', function () {
    $user = settingsUser('acc-in-use@example.com');
    $currency = activateCurrency($user, 'BRL');

    $account = Account::create([
        'tenant_id' => $user->tenant_id,
        'currency_id' => $currency->id,
        'name' => 'Com lançamentos',
        'balance' => 0,
    ]);

    Transaction::create([
        'tenant_id' => $user->tenant_id,
        'account_id' => $account->id,
        'currency_id' => $currency->id,
        'movement_type' => 'expense',
        'type' => 'unique',
        'effective_date' => '2026-08-01',
        'amount' => 50,
        'adjustment_amount' => 0,
        'description' => 'Compra',
    ]);

    actingAs($user)->delete('/accounts/'.$account->id)->assertSessionHasErrors('account');
    expect(Account::query()->whereKey($account->id)->exists())->toBeTrue();
});

test('account creation requires an active currency', function () {
    $user = settingsUser('acc-inactive@example.com');
    $currency = Currency::query()->firstOrCreate(['code' => 'USD'], ['name' => 'Dollar', 'symbol' => '$']);

    actingAs($user)
        ->post('/accounts', ['name' => 'Sem moeda ativa', 'currency_id' => $currency->id])
        ->assertSessionHasErrors('currency_id');
});

test('users may manage the active currencies', function () {
    $user = settingsUser('cur-manage@example.com');
    $brl = Currency::query()->firstOrCreate(['code' => 'BRL'], ['name' => 'Real', 'symbol' => 'R$']);
    $usd = Currency::query()->firstOrCreate(['code' => 'USD'], ['name' => 'Dollar', 'symbol' => '$']);

    actingAs($user)
        ->patch('/currencies', ['currency_ids' => [$brl->id, $usd->id]])
        ->assertRedirect();

    expect($user->tenant()->first()->activeCurrencies()->pluck('code')->sort()->values()->all())
        ->toBe(['BRL', 'USD']);

    actingAs($user)
        ->patch('/currencies', ['currency_ids' => [$brl->id]])
        ->assertRedirect();

    expect($user->tenant()->first()->activeCurrencies()->pluck('code')->all())->toBe(['BRL']);
});

test('a tenant cannot modify another tenant account', function () {
    $victim = settingsUser('victim-acc@example.com');
    $currency = activateCurrency($victim, 'BRL');
    $victimAccount = Account::create([
        'tenant_id' => $victim->tenant_id,
        'name' => 'Privada',
        'currency_id' => $currency->id,
        'balance' => 0,
    ]);

    $attacker = settingsUser('attacker-acc@example.com');
    app(TenantContext::class)->clear();

    actingAs($attacker)
        ->delete('/accounts/'.$victimAccount->id)
        ->assertNotFound();

    expect($victimAccount->fresh())->not->toBeNull();
});
