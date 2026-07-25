<?php

use App\Models\Account;
use App\Models\Currency;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

test('users may review virtual transactions by month with simple calculations', function () {
    $tenant = Tenant::create([
        'name' => 'Tenant Principal',
    ]);

    $user = User::create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa Teste',
        'email' => 'transactions@example.com',
        'password' => 'password',
    ]);

    $currencies = collect([
        ['code' => 'BRL', 'name' => 'Brazilian Real', 'symbol' => 'R$'],
        ['code' => 'ARS', 'name' => 'Argentine Peso', 'symbol' => '$'],
        ['code' => 'USD', 'name' => 'United States Dollar', 'symbol' => '$'],
    ])->mapWithKeys(function (array $data): array {
        return [$data['code'] => Currency::create($data)];
    });

    app(TenantContext::class)->setTenantId($tenant->id);

    $tenant->syncCurrencyActivations($currencies->pluck('id')->all());

    Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currencies['BRL']->id,
        'name' => 'Conta BRL',
        'balance' => 0,
    ]);

    Transaction::query()->create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currencies['BRL']->id,
        'account_id' => Account::query()->where('tenant_id', $tenant->id)->where('currency_id', $currencies['BRL']->id)->value('id'),
        'movement_type' => 'expense',
        'type' => 'recurring',
        'series_uuid' => 'rent-series',
        'effective_date' => '2026-07-01',
        'amount' => 1500,
        'description' => 'Aluguel',
    ]);

    Transaction::query()->create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currencies['BRL']->id,
        'account_id' => Account::query()->where('tenant_id', $tenant->id)->where('currency_id', $currencies['BRL']->id)->value('id'),
        'movement_type' => 'expense',
        'type' => 'recurring',
        'series_uuid' => 'rent-series',
        'effective_date' => '2026-12-01',
        'adjustment_month' => '2026-12-01',
        'amount' => 1700,
        'adjustment_amount' => 200,
        'description' => 'Aluguel dezembro ajustado',
    ]);

    Transaction::query()->create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currencies['ARS']->id,
        'account_id' => Account::factory()->create([
            'tenant_id' => $tenant->id,
            'currency_id' => $currencies['ARS']->id,
        ])->id,
        'movement_type' => 'income',
        'type' => 'unique',
        'effective_date' => '2026-12-15',
        'amount' => 100,
        'description' => 'Serviço avulso',
    ]);

    Transaction::query()->create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currencies['USD']->id,
        'account_id' => Account::factory()->create([
            'tenant_id' => $tenant->id,
            'currency_id' => $currencies['USD']->id,
        ])->id,
        'movement_type' => 'expense',
        'type' => 'installment',
        'installment_frequency' => 'bimonthly',
        'installments_total' => 3,
        'installment_number' => 1,
        'effective_date' => '2026-12-05',
        'amount' => 300,
        'description' => 'Notebook',
    ]);

    $response = actingAs($user)->get('/transactions?period=2026-12');

    $response->assertSuccessful()->assertInertia(function (Assert $page): void {
        $page->component('Transactions/Index')
            ->where('period', '2026-12')
            ->where('totals.entries', 3)
            ->where('totals.unique', 1)
            ->where('totals.recurring', 1)
            ->where('totals.installment', 1)
            ->where('totals.adjustments', 1);
    });

    $currencySummaries = collect($response->inertiaProps('currencySummaries'))->keyBy('code');
    $entries = collect($response->inertiaProps('entries'));

    // Totals are now net cash flow: expenses subtract, income adds.
    expect($currencySummaries['BRL']['total'])->toBe('-1700.00');
    expect($currencySummaries['ARS']['total'])->toBe('100.00');
    expect($currencySummaries['USD']['total'])->toBe('-300.00');
    expect($entries->pluck('kind')->sort()->values()->all())->toBe(['adjustment', 'installment', 'unique']);
});

test('period navigation keeps recurring transactions visible in the target month', function () {
    $tenant = Tenant::create([
        'name' => 'Tenant Principal',
    ]);

    $user = User::create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa Teste',
        'email' => 'transactions-navigation@example.com',
        'password' => 'password',
    ]);

    $currency = Currency::create([
        'code' => 'BRL',
        'name' => 'Brazilian Real',
        'symbol' => 'R$',
    ]);

    app(TenantContext::class)->setTenantId($tenant->id);

    Transaction::query()->create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'account_id' => Account::create([
            'tenant_id' => $tenant->id,
            'currency_id' => $currency->id,
            'name' => 'Conta BRL',
            'balance' => 0,
        ])->id,
        'movement_type' => 'expense',
        'type' => 'recurring',
        'effective_date' => '2026-07-01',
        'amount' => 1500,
        'description' => 'Aluguel',
    ]);

    $response = actingAs($user)->get('/transactions?period=2026-08&date_from=2026-08-01&date_to=2026-08-31');

    $response->assertSuccessful()->assertInertia(function (Assert $page): void {
        $page->component('Transactions/Index')
            ->where('period', '2026-08')
            ->where('totals.recurring', 1);
    });

    expect(collect($response->inertiaProps('entries'))->pluck('kind')->all())->toContain('base');
});
