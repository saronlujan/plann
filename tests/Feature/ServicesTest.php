<?php

use App\Models\Account;
use App\Models\Currency;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

/**
 * A workspace holding one account, which is what makes a currency available for
 * pricing: a service may only be priced in a currency the tenant already keeps.
 *
 * @return array{0: User, 1: Currency}
 */
function servicesUser(string $email): array
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

    // The catalogue is shared, so a second workspace in the same test reuses it
    // rather than colliding on the global unique over the code.
    $currency = Currency::query()->firstOrCreate(
        ['code' => 'BRL'],
        ['name' => 'Brazilian Real', 'symbol' => 'R$'],
    );

    Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Conta BRL',
    ]);

    return [$user, $currency];
}

test('authenticated users may view their services', function () {
    [$user, $currency] = servicesUser('services@example.com');

    Service::create([
        'tenant_id' => $user->tenant_id,
        'name' => 'Hospedagem',
        'default_price' => '50.00',
        'currency_id' => $currency->id,
        'color' => 'blue',
    ]);

    actingAs($user)
        ->get('/services')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Services/Index')
            ->has('services', 1)
            ->has('currencyOptions', 1)
            ->has('colorOptions'));
});

test('users may create a service with a standing price', function () {
    [$user, $currency] = servicesUser('service-create@example.com');

    actingAs($user)
        ->post('/services', [
            'name' => 'Hospedagem',
            'default_price' => '50.00',
            'currency_id' => $currency->id,
            'color' => 'blue',
        ])
        ->assertRedirect();

    $service = Service::query()->where('name', 'Hospedagem')->first();

    expect($service)->not->toBeNull();
    expect($service?->default_price)->toBe('50.00');
    expect($service?->tenant_id)->toBe($user->tenant_id);
});

test('a service may be created without a price', function () {
    [$user] = servicesUser('service-no-price@example.com');

    actingAs($user)
        ->post('/services', ['name' => 'Consultoria', 'color' => 'green'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Service::query()->where('name', 'Consultoria')->first()?->default_price)->toBeNull();
});

test('a priced service requires a currency the workspace holds', function () {
    [$user] = servicesUser('service-currency@example.com');

    actingAs($user)
        ->post('/services', ['name' => 'Suporte', 'default_price' => '250.00', 'color' => 'red'])
        ->assertSessionHasErrors('currency_id');

    $foreign = Currency::create(['code' => 'JPY', 'name' => 'Yen', 'symbol' => '¥']);

    actingAs($user)
        ->post('/services', [
            'name' => 'Suporte',
            'default_price' => '250.00',
            'currency_id' => $foreign->id,
            'color' => 'red',
        ])
        ->assertSessionHasErrors('currency_id');
});

test('service creation rejects blank and duplicate names', function () {
    [$user] = servicesUser('service-valid@example.com');

    actingAs($user)
        ->post('/services', ['name' => '', 'color' => 'blue'])
        ->assertSessionHasErrors('name');

    Service::create(['tenant_id' => $user->tenant_id, 'name' => 'Hospedagem', 'color' => 'blue']);

    actingAs($user)
        ->post('/services', ['name' => 'Hospedagem', 'color' => 'green'])
        ->assertSessionHasErrors('name');
});

test('users may update and delete a service', function () {
    [$user] = servicesUser('service-ud@example.com');
    $service = Service::create(['tenant_id' => $user->tenant_id, 'name' => 'Suporte', 'color' => 'blue']);

    actingAs($user)
        ->patch('/services/'.$service->id, ['name' => 'Suporte Premium', 'color' => 'green'])
        ->assertRedirect();

    $service->refresh();
    expect($service->name)->toBe('Suporte Premium');
    expect($service->color)->toBe('green');

    actingAs($user)->delete('/services/'.$service->id)->assertRedirect();
    expect(Service::query()->whereKey($service->id)->exists())->toBeFalse();
});

test('a tenant cannot modify another tenant service', function () {
    [$victim] = servicesUser('victim-service@example.com');
    $victimService = Service::create(['tenant_id' => $victim->tenant_id, 'name' => 'Privado', 'color' => 'blue']);

    [$attacker] = servicesUser('attacker-service@example.com');
    app(TenantContext::class)->clear();

    actingAs($attacker)
        ->patch('/services/'.$victimService->id, ['name' => 'Hack', 'color' => 'red'])
        ->assertNotFound();

    actingAs($attacker)
        ->delete('/services/'.$victimService->id)
        ->assertNotFound();

    expect($victimService->fresh()?->name)->toBe('Privado');
});

test('deleting a service leaves the money on the transaction untouched', function () {
    [$user, $currency] = servicesUser('service-delete-lines@example.com');

    $account = Account::query()->where('tenant_id', $user->tenant_id)->firstOrFail();
    $hosting = Service::create(['tenant_id' => $user->tenant_id, 'name' => 'Hospedagem', 'color' => 'blue']);
    $support = Service::create(['tenant_id' => $user->tenant_id, 'name' => 'Suporte', 'color' => 'green']);

    $transaction = Transaction::create([
        'tenant_id' => $user->tenant_id,
        'account_id' => $account->id,
        'currency_id' => $currency->id,
        'movement_type' => 'income',
        'type' => 'unique',
        'effective_date' => '2026-07-01',
        'amount' => '300.00',
        'description' => 'Contrato Apple Shop',
    ]);

    $transaction->lines()->createMany([
        ['tenant_id' => $user->tenant_id, 'service_id' => $hosting->id, 'amount' => '50.00'],
        ['tenant_id' => $user->tenant_id, 'service_id' => $support->id, 'amount' => '250.00'],
    ]);

    actingAs($user)->delete('/services/'.$hosting->id)->assertRedirect();

    $lines = DB::table('service_transaction')->where('transaction_id', $transaction->id)->get();

    // The line outlives the service it named: the amount is still recorded, only
    // now with nothing to attribute it to.
    expect($lines)->toHaveCount(2);
    expect($lines->sum(fn (object $line): float => (float) $line->amount))->toBe(300.0);
    expect($lines->firstWhere('service_id', null))->not->toBeNull();
    expect($transaction->fresh()?->amount)->toBe('300.00');
});

test('a service may be painted a colour of the user own choosing', function () {
    [$user] = servicesUser('service-custom-color@example.com');

    actingAs($user)
        ->post('/services', ['name' => 'Tráfego pago', 'color' => '#6361F3'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    // Folded to lowercase, so the same colour is never stored two ways.
    expect(Service::query()->where('name', 'Tráfego pago')->value('color'))->toBe('#6361f3');
});

test('a colour that is neither in the palette nor a hex is rejected', function () {
    [$user] = servicesUser('service-bad-color@example.com');

    foreach (['rebeccapurple', '#63f', '#6361FG', 'blue; background: red', ''] as $color) {
        actingAs($user)
            ->post('/services', ['name' => 'Serviço '.$color, 'color' => $color])
            ->assertSessionHasErrors('color');
    }
});
