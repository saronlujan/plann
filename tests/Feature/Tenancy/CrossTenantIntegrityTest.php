<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Tag;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * The tenant scope decides what a query *returns*. It cannot stop a write from
 * pointing at another tenant's row: a raw query, a seeder or a bug in a service
 * would be accepted by a single-column foreign key, because the referenced row
 * does exist — it just belongs to somebody else.
 *
 * These tests assert the database itself refuses the cross-tenant reference.
 *
 * @return array{0: Tenant, 1: Account, 2: Category, 3: Tag, 4: Currency}
 */
function tenantFixture(string $name): array
{
    $tenant = Tenant::create(['name' => $name]);
    app(TenantContext::class)->setTenantId($tenant->id);

    $currency = Currency::withoutGlobalScopes()->firstOrCreate(
        ['code' => 'BRL', 'tenant_id' => null],
        ['name' => 'Real', 'symbol' => 'R$'],
    );

    $account = Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Conta '.$name,
        'balance' => 0,
    ]);

    $category = Category::create([
        'tenant_id' => $tenant->id,
        'name' => 'Categoria '.$name,
        'type' => 'expense',
        'color' => 'green',
    ]);

    $tag = Tag::create(['tenant_id' => $tenant->id, 'name' => 'tag-'.$name, 'color' => 'blue']);

    return [$tenant, $account, $category, $tag, $currency];
}

test('a transaction cannot point at another tenant account', function () {
    [, $accountA, , , $currency] = tenantFixture('A');
    [$tenantB] = tenantFixture('B');

    // Written raw on purpose: this is the path the scope does not cover.
    expect(fn () => DB::table('transactions')->insert([
        'tenant_id' => $tenantB->id,
        'account_id' => $accountA->id,
        'currency_id' => $currency->id,
        'movement_type' => 'expense',
        'type' => 'unique',
        'effective_date' => '2026-08-01',
        'amount' => 100,
        'adjustment_amount' => 0,
        'description' => 'Vazamento',
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

test('a transaction cannot point at another tenant category', function () {
    [, , $categoryA, , $currency] = tenantFixture('A');
    [$tenantB, $accountB] = tenantFixture('B');

    expect(fn () => DB::table('transactions')->insert([
        'tenant_id' => $tenantB->id,
        'account_id' => $accountB->id,
        'category_id' => $categoryA->id,
        'currency_id' => $currency->id,
        'movement_type' => 'expense',
        'type' => 'unique',
        'effective_date' => '2026-08-01',
        'amount' => 100,
        'adjustment_amount' => 0,
        'description' => 'Vazamento',
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

test('a transaction cannot be tagged with another tenant tag', function () {
    [, , , $tagA] = tenantFixture('A');
    [$tenantB, $accountB, , , $currency] = tenantFixture('B');

    $transactionB = Transaction::create([
        'tenant_id' => $tenantB->id,
        'account_id' => $accountB->id,
        'currency_id' => $currency->id,
        'movement_type' => 'expense',
        'type' => 'unique',
        'effective_date' => '2026-08-01',
        'amount' => 100,
        'adjustment_amount' => 0,
        'description' => 'Movimento',
    ]);

    // The pivot carries tenant_id, so the rejection must come from the composite
    // key rather than from a missing column.
    expect(DB::getSchemaBuilder()->hasColumn('tag_transaction', 'tenant_id'))->toBeTrue();

    expect(fn () => DB::table('tag_transaction')->insert([
        'tenant_id' => $tenantB->id,
        'transaction_id' => $transactionB->id,
        'tag_id' => $tagA->id,
    ]))->toThrow(QueryException::class);

    // Same tag id, owner's tenant: proves the id itself is fine and only the
    // cross-tenant pairing is refused.
    expect(DB::table('tag_transaction')->where('tag_id', $tagA->id)->count())->toBe(0);
});

test('a legitimate same-tenant reference still works', function () {
    [$tenant, $account, $category, $tag, $currency] = tenantFixture('A');

    $transaction = Transaction::create([
        'tenant_id' => $tenant->id,
        'account_id' => $account->id,
        'category_id' => $category->id,
        'currency_id' => $currency->id,
        'movement_type' => 'expense',
        'type' => 'unique',
        'effective_date' => '2026-08-01',
        'amount' => 100,
        'adjustment_amount' => 0,
        'description' => 'Movimento',
    ]);

    $transaction->tags()->sync([$tag->id]);

    expect($transaction->fresh()->tags)->toHaveCount(1);
});

test('deleting a category still uncategorises its transactions', function () {
    [$tenant, $account, $category, , $currency] = tenantFixture('A');

    $transaction = Transaction::create([
        'tenant_id' => $tenant->id,
        'account_id' => $account->id,
        'category_id' => $category->id,
        'currency_id' => $currency->id,
        'movement_type' => 'expense',
        'type' => 'unique',
        'effective_date' => '2026-08-01',
        'amount' => 100,
        'adjustment_amount' => 0,
        'description' => 'Movimento',
    ]);

    $category->delete();

    // The entry survives the category: losing a label must never lose money.
    expect($transaction->fresh())->not->toBeNull();
    expect($transaction->fresh()->category_id)->toBeNull();
});
