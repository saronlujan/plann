<?php

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Support\Tenancy\TenantContext;

test('a recurring transaction keeps one master row and allows monthly adjustments', function () {
    $tenant = Tenant::factory()->create();
    $currency = Currency::factory()->create();

    app(TenantContext::class)->setTenantId($tenant->id);

    $account = Account::factory()->create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
    ]);

    $seriesUuid = (string) str()->uuid();

    $master = Transaction::query()->create([
        'tenant_id' => $tenant->id,
        'account_id' => $account->id,
        'currency_id' => $currency->id,
        'type' => 'recurring',
        'series_uuid' => $seriesUuid,
        'effective_date' => '2026-07-01',
        'amount' => 1500,
        'description' => 'Aluguel',
    ]);

    $exception = Transaction::query()->create([
        'tenant_id' => $tenant->id,
        'account_id' => $account->id,
        'currency_id' => $currency->id,
        'type' => 'recurring',
        'series_uuid' => $seriesUuid,
        'effective_date' => '2026-12-01',
        'adjustment_month' => '2026-12-01',
        'amount' => 1700,
        'adjustment_amount' => 200,
        'description' => 'Aluguel dezembro ajustado',
    ]);

    expect(Transaction::query()->where('series_uuid', $seriesUuid)->count())->toBe(2);
    expect($master->amount)->toBe('1500.00');
    expect($exception->amount)->toBe('1700.00');
    expect($master->type)->toBe(TransactionType::Recurring);
    expect($exception->type)->toBe(TransactionType::Recurring);
    expect($exception->adjustment_month?->format('Y-m'))->toBe('2026-12');
});
