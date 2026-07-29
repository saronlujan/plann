<?php

use App\Models\Account;
use App\Models\Contact;
use App\Models\Currency;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

/**
 * @return array{user: User, currency: Currency, account: Account, hosting: Service, support: Service}
 */
function linesFixture(string $email): array
{
    $tenant = Tenant::create(['name' => 'Tenant '.$email]);
    app(TenantContext::class)->setTenantId($tenant->id);

    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa',
        'email' => $email,
        'password' => 'password',
    ]);

    $currency = Currency::query()->firstOrCreate(
        ['code' => 'BRL'],
        ['name' => 'Brazilian Real', 'symbol' => 'R$'],
    );

    $account = Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Conta BRL',
    ]);

    return [
        'user' => $user,
        'currency' => $currency,
        'account' => $account,
        'hosting' => Service::create([
            'tenant_id' => $tenant->id,
            'name' => 'Hospedagem',
            'default_price' => '50.00',
            'currency_id' => $currency->id,
            'color' => 'blue',
        ]),
        'support' => Service::create([
            'tenant_id' => $tenant->id,
            'name' => 'Suporte',
            'default_price' => '250.00',
            'currency_id' => $currency->id,
            'color' => 'green',
        ]),
    ];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function contractPayload(array $fixture, array $overrides = []): array
{
    return [
        'movement_type' => 'income',
        'type' => 'unique',
        'description' => 'Contrato Apple Shop',
        'currency_id' => $fixture['currency']->id,
        'account_id' => $fixture['account']->id,
        'effective_date' => '2026-07-01',
        'amount' => '300.00',
        ...$overrides,
    ];
}

test('the amount is taken from the lines, not from what the form sent', function () {
    $fixture = linesFixture('lines-store@example.com');

    actingAs($fixture['user'])
        ->post('/transactions', contractPayload($fixture, [
            // A crafted total that contradicts its own parts is ignored: the
            // field is read-only in the form once services are attached.
            'amount' => '999.00',
            'services' => [
                ['service_id' => $fixture['hosting']->id, 'amount' => '50.00'],
                ['service_id' => $fixture['support']->id, 'amount' => '250.00'],
            ],
        ]))
        ->assertRedirect();

    $transaction = Transaction::query()->firstOrFail();

    expect($transaction->amount)->toBe('300.00');
    expect($transaction->lines)->toHaveCount(2);
    expect($transaction->lines->sum(fn ($line): float => (float) $line->amount))->toBe(300.0);
});

test('a transaction with no lines keeps the amount that was typed', function () {
    $fixture = linesFixture('lines-none@example.com');

    actingAs($fixture['user'])
        ->post('/transactions', contractPayload($fixture))
        ->assertRedirect();

    $transaction = Transaction::query()->firstOrFail();

    expect($transaction->amount)->toBe('300.00');
    expect($transaction->lines)->toHaveCount(0);
});

test('editing the lines moves the amount with them', function () {
    $fixture = linesFixture('lines-update@example.com');

    actingAs($fixture['user'])->post('/transactions', contractPayload($fixture, [
        'services' => [
            ['service_id' => $fixture['hosting']->id, 'amount' => '50.00'],
            ['service_id' => $fixture['support']->id, 'amount' => '250.00'],
        ],
    ]));

    $transaction = Transaction::query()->firstOrFail();

    // The client negotiated the support line down; the total follows.
    actingAs($fixture['user'])
        ->patch('/transactions/'.$transaction->id, contractPayload($fixture, [
            'amount' => '300.00',
            'services' => [
                ['service_id' => $fixture['hosting']->id, 'amount' => '50.00'],
                ['service_id' => $fixture['support']->id, 'amount' => '200.00'],
            ],
        ]))
        ->assertRedirect();

    $transaction->refresh();

    expect($transaction->amount)->toBe('250.00');
    expect($transaction->lines)->toHaveCount(2);
});

test('an edit that never mentions services leaves the breakdown alone', function () {
    $fixture = linesFixture('lines-untouched@example.com');

    actingAs($fixture['user'])->post('/transactions', contractPayload($fixture, [
        'services' => [
            ['service_id' => $fixture['hosting']->id, 'amount' => '50.00'],
            ['service_id' => $fixture['support']->id, 'amount' => '250.00'],
        ],
    ]));

    $transaction = Transaction::query()->firstOrFail();

    actingAs($fixture['user'])
        ->patch('/transactions/'.$transaction->id, contractPayload($fixture, [
            'description' => 'Contrato Apple Shop (renomeado)',
        ]))
        ->assertRedirect();

    $transaction->refresh();

    expect($transaction->description)->toBe('Contrato Apple Shop (renomeado)');
    expect($transaction->lines)->toHaveCount(2);
});

test('a line left behind by a retired service survives the round trip', function () {
    $fixture = linesFixture('lines-orphan@example.com');

    actingAs($fixture['user'])->post('/transactions', contractPayload($fixture, [
        'services' => [
            ['service_id' => $fixture['hosting']->id, 'amount' => '50.00'],
            ['service_id' => $fixture['support']->id, 'amount' => '250.00'],
        ],
    ]));

    $transaction = Transaction::query()->firstOrFail();

    actingAs($fixture['user'])->delete('/services/'.$fixture['hosting']->id);

    // The form hands the orphaned line back untouched, so the total holds.
    actingAs($fixture['user'])
        ->patch('/transactions/'.$transaction->id, contractPayload($fixture, [
            'services' => [
                ['service_id' => null, 'amount' => '50.00'],
                ['service_id' => $fixture['support']->id, 'amount' => '250.00'],
            ],
        ]))
        ->assertRedirect();

    $transaction->refresh();

    expect($transaction->amount)->toBe('300.00');
    expect($transaction->lines)->toHaveCount(2);
    expect($transaction->lines->firstWhere('service_id', null)?->amount)->toBe('50.00');
});

test('naming the same service twice folds into one line', function () {
    $fixture = linesFixture('lines-duplicate@example.com');

    actingAs($fixture['user'])
        ->post('/transactions', contractPayload($fixture, [
            'services' => [
                ['service_id' => $fixture['hosting']->id, 'amount' => '50.00'],
                ['service_id' => $fixture['hosting']->id, 'amount' => '30.00'],
            ],
        ]))
        ->assertRedirect();

    $transaction = Transaction::query()->firstOrFail();

    expect($transaction->lines)->toHaveCount(1);
    expect($transaction->amount)->toBe('80.00');
});

test('lines and contacts are rejected when they belong to another workspace', function () {
    $victim = linesFixture('lines-victim@example.com');
    $foreignService = $victim['hosting'];
    $foreignContact = Contact::create([
        'tenant_id' => $victim['user']->tenant_id,
        'name' => 'Cliente alheio',
        'type' => 'client',
    ]);

    $attacker = linesFixture('lines-attacker@example.com');

    actingAs($attacker['user'])
        ->post('/transactions', contractPayload($attacker, [
            'services' => [['service_id' => $foreignService->id, 'amount' => '50.00']],
        ]))
        ->assertSessionHasErrors('services.0.service_id');

    actingAs($attacker['user'])
        ->post('/transactions', contractPayload($attacker, [
            'contact_id' => $foreignContact->id,
        ]))
        ->assertSessionHasErrors('contact_id');
});

test('a transaction records who it was with', function () {
    $fixture = linesFixture('lines-contact@example.com');

    $client = Contact::create([
        'tenant_id' => $fixture['user']->tenant_id,
        'name' => 'Apple Shop',
        'type' => 'client',
    ]);

    actingAs($fixture['user'])
        ->post('/transactions', contractPayload($fixture, ['contact_id' => $client->id]))
        ->assertRedirect();

    expect(Transaction::query()->firstOrFail()->contact_id)->toBe($client->id);
});

test('the breakdown is handed back to the transactions screen', function () {
    $fixture = linesFixture('lines-index@example.com');

    actingAs($fixture['user'])->post('/transactions', contractPayload($fixture, [
        'services' => [
            ['service_id' => $fixture['hosting']->id, 'amount' => '50.00'],
            ['service_id' => $fixture['support']->id, 'amount' => '250.00'],
        ],
    ]));

    actingAs($fixture['user'])
        ->get('/transactions?period=2026-07')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Transactions/Index')
            ->has('serviceOptions', 2)
            ->has('contactOptions')
            ->has('entries.0.services', 2));
});
