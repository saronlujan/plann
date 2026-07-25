<?php

use App\Models\Account;
use App\Models\Currency;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Tenancy\TenantContext;

use function Pest\Laravel\actingAs;

/**
 * @return array{tenant: Tenant, user: User, account: Account, transaction: Transaction, currency: Currency}
 */
function makeTenantWithTransaction(string $email): array
{
    $tenant = Tenant::create(['name' => 'Tenant '.$email]);
    $user = User::create([
        'tenant_id' => $tenant->id,
        'name' => 'Owner '.$email,
        'email' => $email,
        'password' => 'password',
    ]);
    $currency = Currency::query()->firstOrCreate(
        ['code' => 'BRL'],
        ['name' => 'Brazilian Real', 'symbol' => 'R$'],
    );

    app(TenantContext::class)->setTenantId($tenant->id);
    $tenant->syncCurrencyActivations([$currency->id]);

    $account = Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Conta BRL',
        'balance' => 0,
    ]);

    $transaction = Transaction::query()->create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'account_id' => $account->id,
        'movement_type' => 'expense',
        'type' => 'unique',
        'effective_date' => '2026-07-10',
        'amount' => 450,
        'description' => 'Internet',
    ]);

    return compact('tenant', 'user', 'account', 'transaction', 'currency');
}

it('does not let a tenant update another tenant transaction', function () {
    $victim = makeTenantWithTransaction('victim-update@example.com');
    $attacker = makeTenantWithTransaction('attacker-update@example.com');

    actingAs($attacker['user'])
        ->patch('/transactions/'.$victim['transaction']->id, [
            'movement_type' => 'expense',
            'type' => 'unique',
            'description' => 'Hijacked',
            'currency_id' => $attacker['currency']->id,
            'account_id' => $attacker['account']->id,
            'effective_date' => '2026-07-10',
            'amount' => 1,
        ])
        ->assertNotFound();

    expect($victim['transaction']->fresh()?->description)->toBe('Internet');
});

it('does not let a tenant mark another tenant transaction as paid', function () {
    $victim = makeTenantWithTransaction('victim-pay@example.com');
    $attacker = makeTenantWithTransaction('attacker-pay@example.com');

    actingAs($attacker['user'])
        ->patch('/transactions/'.$victim['transaction']->id.'/pay')
        ->assertNotFound();

    expect($victim['transaction']->fresh()?->paid_at)->toBeNull();
});

it('rejects storing a transaction against another tenant account', function () {
    $victim = makeTenantWithTransaction('victim-store@example.com');
    $attacker = makeTenantWithTransaction('attacker-store@example.com');

    actingAs($attacker['user'])
        ->post('/transactions', [
            'movement_type' => 'expense',
            'type' => 'unique',
            'description' => 'Cross tenant',
            'currency_id' => $victim['currency']->id,
            'account_id' => $victim['account']->id,
            'effective_date' => '2026-07-10',
            'amount' => 100,
        ])
        ->assertSessionHasErrors('account_id');
});

it('rejects installment counts above the allowed maximum', function () {
    $data = makeTenantWithTransaction('installments-cap@example.com');

    actingAs($data['user'])
        ->post('/transactions', [
            'movement_type' => 'expense',
            'type' => 'installment',
            'description' => 'Too many',
            'currency_id' => $data['currency']->id,
            'account_id' => $data['account']->id,
            'effective_date' => '2026-07-10',
            'amount' => 100,
            'installment_frequency' => 'monthly',
            'installments_total' => 5000,
        ])
        ->assertSessionHasErrors('installments_total');
});
