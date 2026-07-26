<?php

use App\Models\Currency;
use App\Models\Goal;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;

use function Pest\Laravel\actingAs;

/**
 * @return array{0: User, 1: Currency}
 */
function goalFixture(string $email): array
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

    return [$user, $currency];
}

test('users may create a goal', function () {
    [$user, $currency] = goalFixture('goal-create@example.com');

    actingAs($user)
        ->post('/goals', [
            'name' => 'Reserva',
            'currency_id' => $currency->id,
            'target_amount' => 5000,
            'current_amount' => 1000,
        ])
        ->assertRedirect();

    $goal = Goal::query()->where('name', 'Reserva')->first();
    expect($goal)->not->toBeNull();
    expect($goal?->target_amount)->toBe('5000.00');
    expect($goal?->current_amount)->toBe('1000.00');
});

test('the target amount is required and positive', function () {
    [$user, $currency] = goalFixture('goal-invalid@example.com');

    actingAs($user)
        ->post('/goals', ['name' => 'X', 'currency_id' => $currency->id, 'target_amount' => 0])
        ->assertSessionHasErrors('target_amount');
});

test('users may contribute to a goal', function () {
    [$user, $currency] = goalFixture('goal-contribute@example.com');
    $goal = Goal::create([
        'tenant_id' => $user->tenant_id,
        'currency_id' => $currency->id,
        'name' => 'Viagem',
        'target_amount' => 3000,
        'current_amount' => 500,
    ]);

    actingAs($user)->post('/goals/'.$goal->id.'/contribute', ['amount' => 250])->assertRedirect();

    expect($goal->fresh()?->current_amount)->toBe('750.00');
});

test('users may update and delete a goal', function () {
    [$user, $currency] = goalFixture('goal-ud@example.com');
    $goal = Goal::create([
        'tenant_id' => $user->tenant_id,
        'currency_id' => $currency->id,
        'name' => 'Meta',
        'target_amount' => 1000,
        'current_amount' => 0,
    ]);

    actingAs($user)
        ->patch('/goals/'.$goal->id, [
            'name' => 'Meta nova',
            'currency_id' => $currency->id,
            'target_amount' => 2000,
            'current_amount' => 0,
        ])
        ->assertRedirect();
    expect($goal->fresh()?->name)->toBe('Meta nova');

    actingAs($user)->delete('/goals/'.$goal->id)->assertRedirect();
    expect(Goal::query()->whereKey($goal->id)->exists())->toBeFalse();
});

test('a tenant cannot contribute to another tenant goal', function () {
    [$victim, $currency] = goalFixture('goal-victim@example.com');
    $goal = Goal::create([
        'tenant_id' => $victim->tenant_id,
        'currency_id' => $currency->id,
        'name' => 'Privada',
        'target_amount' => 1000,
        'current_amount' => 0,
    ]);

    [$attacker] = goalFixture('goal-attacker@example.com');
    app(TenantContext::class)->clear();

    actingAs($attacker)->post('/goals/'.$goal->id.'/contribute', ['amount' => 100])->assertNotFound();
    expect($goal->fresh()?->current_amount)->toBe('0.00');
});
