<?php

use App\Models\Account;
use App\Models\Currency;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Tenancy\TenantContext;

use function Pest\Laravel\actingAs;

it('updates a transaction from the edit modal', function () {
    $tenant = Tenant::create([
        'name' => 'Tenant Principal',
    ]);

    $user = User::create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa Teste',
        'email' => 'update-transactions@example.com',
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

    $transaction = Transaction::query()->create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'movement_type' => 'expense',
        'type' => 'unique',
        'effective_date' => '2026-07-10',
        'amount' => 450,
        'description' => 'Internet',
        'account_id' => $account->id,
    ]);

    actingAs($user)
        ->patch('/transactions/'.$transaction->id, [
            'movement_type' => 'income',
            'type' => 'recurring',
            'description' => 'Internet corporate',
            'currency_id' => $currency->id,
            'account_id' => $account->id,
            'effective_date' => '2026-08-01',
            'amount' => 500,
            'adjustment_amount' => 0,
            'adjustment_month' => null,
            'installment_frequency' => 'monthly',
            'installments_total' => null,
            'installment_number' => null,
        ])
        ->assertRedirect('/transactions?period=2026-08');

    $transaction->refresh();

    expect($transaction->description)->toBe('Internet corporate');
    expect($transaction->movement_type)->toBe('income');
    expect($transaction->type)->toBe('recurring');
    expect($transaction->amount)->toBe('500.00');
});

it('updates only one recurring occurrence when requested', function () {
    $tenant = Tenant::create([
        'name' => 'Tenant Principal',
    ]);

    $user = User::create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa Teste',
        'email' => 'update-recurring-one@example.com',
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

    $transaction = Transaction::query()->create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'movement_type' => 'expense',
        'type' => 'recurring',
        'effective_date' => '2026-07-01',
        'amount' => 1500,
        'description' => 'Aluguel',
        'account_id' => $account->id,
    ]);

    actingAs($user)
        ->patch('/transactions/'.$transaction->id, [
            'movement_type' => 'expense',
            'type' => 'recurring',
            'recurrence_scope' => 'one',
            'occurrence_date' => '2026-08-01',
            'description' => 'Aluguel ajustado',
            'currency_id' => $currency->id,
            'account_id' => $account->id,
            'effective_date' => '2026-07-01',
            'amount' => 1700,
            'adjustment_amount' => 0,
            'adjustment_month' => null,
            'installment_frequency' => null,
            'installments_total' => null,
            'installment_number' => null,
        ])
        ->assertRedirect('/transactions?period=2026-08');

    expect(Transaction::query()->count())->toBe(2);
    expect(Transaction::query()->whereDate('adjustment_month', '2026-08-01')->exists())->toBeTrue();
    expect(Transaction::query()->where('description', 'Aluguel')->exists())->toBeTrue();

    $response = actingAs($user)->get('/transactions?period=2026-08');

    expect(collect($response->inertiaProps('entries'))->count())->toBe(1);
    expect(collect($response->inertiaProps('entries'))->first()['kind'])->toBe('adjustment');
});

it('splits a recurring series from the selected month forward', function () {
    $tenant = Tenant::create([
        'name' => 'Tenant Principal',
    ]);

    $user = User::create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa Teste',
        'email' => 'update-recurring-forward@example.com',
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

    $transaction = Transaction::query()->create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'movement_type' => 'expense',
        'type' => 'recurring',
        'effective_date' => '2026-07-01',
        'amount' => 1500,
        'description' => 'Aluguel',
        'account_id' => $account->id,
    ]);

    actingAs($user)
        ->patch('/transactions/'.$transaction->id, [
            'movement_type' => 'expense',
            'type' => 'recurring',
            'recurrence_scope' => 'forward',
            'occurrence_date' => '2026-08-01',
            'description' => 'Aluguel novo valor',
            'currency_id' => $currency->id,
            'account_id' => $account->id,
            'effective_date' => '2026-07-01',
            'amount' => 1700,
            'adjustment_amount' => 0,
            'adjustment_month' => null,
            'installment_frequency' => null,
            'installments_total' => null,
            'installment_number' => null,
        ])
        ->assertRedirect('/transactions?period=2026-08');

    $transaction->refresh();

    $splitTransaction = Transaction::query()
        ->where('description', 'Aluguel novo valor')
        ->whereDate('effective_date', '2026-08-01')
        ->first();

    expect($transaction->effective_until?->format('Y-m-d'))->toBe('2026-07-31');
    expect($splitTransaction)->not->toBeNull();
    expect($splitTransaction?->amount)->toBe('1700.00');
});
