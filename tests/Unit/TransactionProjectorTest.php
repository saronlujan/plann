<?php

use App\Models\Account;
use App\Models\Currency;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Support\Tenancy\TenantContext;
use App\Support\Transactions\TransactionProjector;
use Carbon\CarbonImmutable;

it('expands installments and computes net currency totals in isolation', function () {
    $tenant = Tenant::factory()->create();
    $currency = Currency::factory()->create(['code' => 'BRL', 'name' => 'Real', 'symbol' => 'R$']);

    app(TenantContext::class)->setTenantId($tenant->id);

    $account = Account::factory()->create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Conta',
    ]);

    Transaction::factory()->create([
        'tenant_id' => $tenant->id,
        'account_id' => $account->id,
        'currency_id' => $currency->id,
        'movement_type' => 'expense',
        'type' => 'installment',
        'installment_frequency' => 'monthly',
        'installments_total' => 3,
        'installment_number' => 1,
        'series_uuid' => (string) str()->uuid(),
        'effective_date' => '2026-01-10',
        'amount' => 100,
        'description' => 'Notebook',
    ]);

    Transaction::factory()->create([
        'tenant_id' => $tenant->id,
        'account_id' => $account->id,
        'currency_id' => $currency->id,
        'movement_type' => 'income',
        'type' => 'unique',
        'series_uuid' => null,
        'effective_date' => '2026-01-15',
        'amount' => 250,
        'description' => 'Serviço',
    ]);

    $transactions = Transaction::query()->with(['currency', 'account'])->get();
    $projector = new TransactionProjector;
    $period = CarbonImmutable::parse('2026-01-01');

    $entries = $projector->entriesForPeriod($transactions, $period);

    // January shows the first installment occurrence plus the one-off income.
    expect($entries)->toHaveCount(2);
    expect($entries->pluck('kind')->sort()->values()->all())->toBe(['installment', 'unique']);

    $summaries = $projector->summaries(collect([$currency]), $entries);
    $brl = $summaries->firstWhere('code', 'BRL');

    // Nothing is paid yet, so realized totals are zero.
    expect($brl['total'])->toBe('0.00');
    // Expected reflects every projected entry: 250 income - 100 expense = 150.
    expect($brl['expected_income'])->toBe('250.00');
    expect($brl['expected_expense'])->toBe('100.00');
    expect($brl['expected_total'])->toBe('150.00');
});
