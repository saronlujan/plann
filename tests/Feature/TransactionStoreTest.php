<?php

use App\Models\Account;
use App\Models\Currency;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Tenancy\TenantContext;

use function Pest\Laravel\actingAs;

it('stores a transaction from the insertion modal', function () {
    $tenant = Tenant::create([
        'name' => 'Tenant Principal',
    ]);

    $user = User::create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa Teste',
        'email' => 'store-transactions@example.com',
        'password' => 'password',
    ]);

    $currency = Currency::create([
        'code' => 'BRL',
        'name' => 'Brazilian Real',
        'symbol' => 'R$',
    ]);

    app(TenantContext::class)->setTenantId($tenant->id);

    $account = Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Conta BRL',
        'balance' => 0,
    ]);

    actingAs($user)
        ->post('/transactions', [
            'movement_type' => 'expense',
            'type' => 'installment',
            'description' => 'Notebook',
            'currency_id' => $currency->id,
            'account_id' => $account->id,
            'effective_date' => '2026-12-15',
            'amount' => 3600,
            'adjustment_amount' => 0,
            'adjustment_month' => null,
            'installment_frequency' => 'monthly',
            'installments_total' => 12,
            'installment_number' => 1,
        ])
        ->assertRedirect('/transactions?period=2026-12');

    $transaction = Transaction::query()->first();

    expect($transaction?->description)->toBe('Notebook');
    expect($transaction?->series_uuid)->not->toBeEmpty();
    expect($transaction?->tenant_id)->toBe($tenant->id);
    expect($transaction?->movement_type)->toBe('expense');
});
