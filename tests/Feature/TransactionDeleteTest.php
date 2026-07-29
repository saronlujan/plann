<?php

use App\Models\Account;
use App\Models\Currency;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

/**
 * @return array{0: User, 1: Transaction}
 */
function recurringFixture(string $email): array
{
    $tenant = Tenant::create(['name' => 'Tenant '.$email]);

    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa Teste',
        'email' => $email,
        'password' => 'password',
    ]);

    $currency = Currency::query()->firstOrCreate(
        ['code' => 'BRL'],
        ['name' => 'Brazilian Real', 'symbol' => 'R$'],
    );

    app(TenantContext::class)->setTenantId($tenant->id);

    $account = Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Conta BRL',
    ]);

    $transaction = Transaction::query()->create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'account_id' => $account->id,
        'movement_type' => 'expense',
        'type' => 'recurring',
        'series_uuid' => (string) Str::uuid(),
        'effective_date' => '2026-07-01',
        'amount' => 1500,
        'description' => 'Aluguel',
    ]);

    return [$user, $transaction];
}

it('removes a single occurrence of a recurring series', function () {
    [$user, $transaction] = recurringFixture('delete-recurring-one@example.com');

    actingAs($user)
        ->delete('/transactions/'.$transaction->id, [
            'recurrence_scope' => 'one',
            'occurrence_date' => '2026-09-01',
        ])
        ->assertRedirect('/transactions?period=2026-09');

    // The master is what the projector expands, so the month it no longer
    // produces is recorded rather than deleted.
    expect(Transaction::query()->whereKey($transaction->id)->exists())->toBeTrue();

    $september = actingAs($user)->get('/transactions?period=2026-09');
    $october = actingAs($user)->get('/transactions?period=2026-10');

    expect(collect($september->inertiaProps('entries'))->count())->toBe(0);
    expect(collect($october->inertiaProps('entries'))->count())->toBe(1);
});

it('replaces an edited occurrence when that same month is removed', function () {
    [$user, $transaction] = recurringFixture('delete-recurring-edited@example.com');

    Transaction::query()->create([
        'tenant_id' => $transaction->tenant_id,
        'currency_id' => $transaction->currency_id,
        'account_id' => $transaction->account_id,
        'movement_type' => 'expense',
        'type' => 'recurring',
        'series_uuid' => $transaction->series_uuid,
        'effective_date' => '2026-07-01',
        'adjustment_month' => '2026-09-01',
        'amount' => 1700,
        'description' => 'Aluguel ajustado',
    ]);

    actingAs($user)
        ->delete('/transactions/'.$transaction->id, [
            'recurrence_scope' => 'one',
            'occurrence_date' => '2026-09-01',
        ]);

    // Leaving the edited row in place would bring the month straight back.
    $september = actingAs($user)->get('/transactions?period=2026-09');

    expect(collect($september->inertiaProps('entries'))->count())->toBe(0);
    expect(Transaction::query()->where('description', 'Aluguel ajustado')->exists())->toBeFalse();
});

it('ends a recurring series at the occurrence it was cut from', function () {
    [$user, $transaction] = recurringFixture('delete-recurring-forward@example.com');

    actingAs($user)
        ->delete('/transactions/'.$transaction->id, [
            'recurrence_scope' => 'forward',
            'occurrence_date' => '2026-09-01',
        ])
        ->assertRedirect('/transactions?period=2026-09');

    expect($transaction->refresh()->effective_until->toDateString())->toBe('2026-08-31');

    $august = actingAs($user)->get('/transactions?period=2026-08');
    $september = actingAs($user)->get('/transactions?period=2026-09');

    expect(collect($august->inertiaProps('entries'))->count())->toBe(1);
    expect(collect($september->inertiaProps('entries'))->count())->toBe(0);
});

it('removes the whole series when every entry is chosen', function () {
    [$user, $transaction] = recurringFixture('delete-recurring-all@example.com');

    Transaction::query()->create([
        'tenant_id' => $transaction->tenant_id,
        'currency_id' => $transaction->currency_id,
        'account_id' => $transaction->account_id,
        'movement_type' => 'expense',
        'type' => 'recurring',
        'series_uuid' => $transaction->series_uuid,
        'effective_date' => '2026-07-01',
        'adjustment_month' => '2026-09-01',
        'amount' => 1700,
        'description' => 'Aluguel ajustado',
    ]);

    actingAs($user)
        ->delete('/transactions/'.$transaction->id, ['recurrence_scope' => 'all']);

    // Occurrences edited on their own are rows of the series too.
    expect(Transaction::query()->count())->toBe(0);
});

it('deletes a one-off entry outright, scope or not', function () {
    [$user, $transaction] = recurringFixture('delete-unique@example.com');

    $transaction->update(['type' => 'unique', 'series_uuid' => null]);

    actingAs($user)
        ->delete('/transactions/'.$transaction->id, ['recurrence_scope' => 'one'])
        ->assertRedirect('/transactions?period=2026-07');

    // Nothing to expand, so there is no month to mark as removed.
    expect(Transaction::query()->count())->toBe(0);
});
