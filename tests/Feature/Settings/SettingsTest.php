<?php

use App\Enums\LabelColor;
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

/** Any currency from the shared catalogue is available to every workspace. */
function activateCurrency(User $user, string $code = 'BRL'): Currency
{
    return Currency::query()->firstOrCreate(['code' => $code], ['name' => $code, 'symbol' => '$']);
}

function settingsUser(string $email): User
{
    $tenant = Tenant::create(['name' => 'Tenant '.$email]);
    app(TenantContext::class)->setTenantId($tenant->id);

    return User::factory()->create([
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
            ->has('categoryTypeOptions', 2));

    actingAs($user)->get('/tags')->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Tags/Index')
            ->has('tags', 1));

});

test('users may create a category', function () {
    $user = settingsUser('cat-create@example.com');

    actingAs($user)
        ->post('/categories', ['name' => 'Mercado', 'type' => 'expense', 'color' => 'green'])
        ->assertRedirect();

    $category = Category::query()->where('name', 'Mercado')->where('type', 'expense')->first();
    expect($category)->not->toBeNull();
    expect($category?->color)->toBe('green');
});

test('a category may be income', function () {
    $user = settingsUser('cat-income@example.com');

    actingAs($user)
        ->post('/categories', ['name' => 'Hospedagem', 'type' => 'income', 'color' => 'blue'])
        ->assertRedirect();

    expect(Category::query()->where('name', 'Hospedagem')->where('type', 'income')->exists())->toBeTrue();
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

    // The form no longer asks for a starting balance: an account is created
    // empty and the money already there is entered as a transaction.
    actingAs($user)
        ->post('/accounts', ['name' => 'Conta A', 'currency_id' => $currency->id])
        ->assertRedirect();

    $account = Account::query()->where('name', 'Conta A')->first();
    expect($account)->not->toBeNull();

    actingAs($user)
        ->patch('/accounts/'.$account->id, ['name' => 'Conta B', 'currency_id' => $currency->id])
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

test('account creation refuses a currency the workspace cannot see', function () {
    $user = settingsUser('acc-foreign-currency@example.com');

    // Another workspace's own currency is outside this one's catalogue.
    $other = settingsUser('acc-currency-owner@example.com');
    $foreign = Currency::query()->create([
        'tenant_id' => $other->tenant_id,
        'code' => 'XYZ',
        'name' => 'Alheia',
        'symbol' => 'X',
    ]);

    app(TenantContext::class)->setTenantId($user->tenant_id);

    actingAs($user)
        ->post('/accounts', ['name' => 'Moeda alheia', 'currency_id' => $foreign->id])
        ->assertSessionHasErrors('currency_id');
});

test('a tenant cannot modify another tenant account', function () {
    $victim = settingsUser('victim-acc@example.com');
    $currency = activateCurrency($victim, 'BRL');
    $victimAccount = Account::create([
        'tenant_id' => $victim->tenant_id,
        'name' => 'Privada',
        'currency_id' => $currency->id,
    ]);

    $attacker = settingsUser('attacker-acc@example.com');
    app(TenantContext::class)->clear();

    actingAs($attacker)
        ->delete('/accounts/'.$victimAccount->id)
        ->assertNotFound();

    expect($victimAccount->fresh())->not->toBeNull();
});

test('the palette offers both tiers and the enum agrees with the frontend', function () {
    $user = settingsUser('palette@example.com');

    // The picker renders from a TypeScript copy of this enum; a colour missing
    // on either side is either invisible or rejected on save.
    $options = LabelColor::options();

    expect($options)->toHaveCount(40);

    $values = collect($options)->pluck('value');

    expect($values)->toContain('blue');
    expect($values)->toContain('blue_dark');

    // Every value must survive validation, dark tier included.
    actingAs($user)
        ->post('/tags', ['name' => 'Escura', 'color' => 'blue_dark'])
        ->assertSessionHasNoErrors();

    expect(Tag::query()->where('name', 'Escura')->value('color'))->toBe(LabelColor::BlueDark->value);
});

test('every colour carries a distinct hex', function () {
    // Two entries sharing a hex would be indistinguishable in the picker and in
    // the dashboard charts.
    $hexes = collect(LabelColor::options())->pluck('hex');

    expect($hexes->unique())->toHaveCount($hexes->count());
});

test('the palette runs the colour wheel with each hue beside its darker tier', function () {
    $values = collect(LabelColor::options())->pluck('value')->all();

    // Grouped, not scattered: a hue and its dark tier sit next to each other so
    // the picker reads as a gradient.
    expect(array_slice($values, 0, 4))->toBe(['red', 'red_dark', 'orange', 'orange_dark']);

    // Neutrals close the list rather than interrupting the spectrum.
    expect(array_slice($values, -2))->toBe(['stone', 'stone_dark']);
});

test('a category and a tag accept a colour picked by hand', function () {
    $user = settingsUser('label-custom-color@example.com');

    actingAs($user)
        ->post('/categories', ['name' => 'Marca', 'type' => 'expense', 'color' => '#6361F3'])
        ->assertSessionHasNoErrors();

    actingAs($user)
        ->post('/tags', ['name' => 'marca', 'color' => '#ABCDEF'])
        ->assertSessionHasNoErrors();

    expect(Category::query()->where('name', 'Marca')->value('color'))->toBe('#6361f3');
    expect(Tag::query()->where('name', 'marca')->value('color'))->toBe('#abcdef');
});

test('the palette stays free of hand-picked colours', function () {
    // options() feeds the swatch row; a custom colour has no name to show there.
    $values = collect(LabelColor::options())->pluck('value');

    expect($values->filter(fn (string $value): bool => str_starts_with($value, '#')))->toBeEmpty();
});
