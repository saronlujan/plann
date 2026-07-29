<?php

use App\Models\Account;
use App\Models\Currency;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use App\Support\Transactions\TransactionAttachments;
use Illuminate\Http\UploadedFile;

use function Pest\Laravel\actingAs;

/**
 * @return array{tenant: Tenant, user: User, account: Account, transaction: Transaction, currency: Currency}
 */
function makeTenantWithTransaction(string $email): array
{
    $tenant = Tenant::create(['name' => 'Tenant '.$email]);
    $user = User::factory()->create([
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

it('resolves route-bound transactions from the middleware tenant context', function () {
    $data = makeTenantWithTransaction('middleware-binding@example.com');

    // Simulate a real request: the tenant context is not pre-set, it must be
    // established by EnsureTenantContext before route-model binding runs.
    app(TenantContext::class)->clear();

    actingAs($data['user'])
        ->patch('/transactions/'.$data['transaction']->id.'/pay')
        ->assertRedirect();

    expect($data['transaction']->fresh()?->paid_at)->not->toBeNull();

    app(TenantContext::class)->clear();

    actingAs($data['user'])
        ->patch('/transactions/'.$data['transaction']->id, [
            'movement_type' => 'expense',
            'type' => 'unique',
            'description' => 'Editado',
            'currency_id' => $data['currency']->id,
            'account_id' => $data['account']->id,
            'effective_date' => '2026-07-10',
            'amount' => 10,
        ])
        ->assertRedirect();

    expect($data['transaction']->fresh()?->description)->toBe('Editado');

    app(TenantContext::class)->clear();

    actingAs($data['user'])
        ->delete('/transactions/'.$data['transaction']->id)
        ->assertRedirect();

    expect(Transaction::query()->whereKey($data['transaction']->id)->exists())->toBeFalse();
});

it('lets a tenant delete its own transaction', function () {
    $data = makeTenantWithTransaction('delete-own@example.com');

    actingAs($data['user'])
        ->delete('/transactions/'.$data['transaction']->id)
        ->assertRedirect();

    expect(Transaction::query()->whereKey($data['transaction']->id)->exists())->toBeFalse();
});

it('does not let a tenant delete another tenant transaction', function () {
    $victim = makeTenantWithTransaction('victim-delete@example.com');
    $attacker = makeTenantWithTransaction('attacker-delete@example.com');

    actingAs($attacker['user'])
        ->delete('/transactions/'.$victim['transaction']->id)
        ->assertNotFound();

    expect($victim['transaction']->fresh())->not->toBeNull();
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

it('rejects amounts with more than two decimal places', function () {
    $data = makeTenantWithTransaction('decimal-amount@example.com');

    actingAs($data['user'])
        ->post('/transactions', [
            'movement_type' => 'expense',
            'type' => 'unique',
            'description' => 'Fração inválida',
            'currency_id' => $data['currency']->id,
            'account_id' => $data['account']->id,
            'effective_date' => '2026-07-10',
            'amount' => 19.999,
        ])
        ->assertSessionHasErrors('amount');
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

it('serves a transaction attachment to its owner', function () {
    $data = makeTenantWithTransaction('attachment-owner@example.com');

    $fileName = app(TransactionAttachments::class)->store(
        UploadedFile::fake()->create('recibo.pdf', 10, 'application/pdf'),
    );

    $data['transaction']->update(['attachment' => $fileName]);

    actingAs($data['user'])
        ->get(route('transactions.attachment', $data['transaction']))
        ->assertOk()
        // Never rendered inline on our own origin.
        ->assertHeader('content-disposition', 'attachment; filename='.$fileName)
        ->assertHeader('x-content-type-options', 'nosniff');
});

it('does not let a tenant download another tenant transaction attachment', function () {
    $victim = makeTenantWithTransaction('attachment-victim@example.com');

    app(TenantContext::class)->setTenantId($victim['tenant']->id);
    $fileName = app(TransactionAttachments::class)->store(
        UploadedFile::fake()->create('recibo.pdf', 10, 'application/pdf'),
    );
    $victim['transaction']->update(['attachment' => $fileName]);

    $attacker = makeTenantWithTransaction('attachment-attacker@example.com');

    actingAs($attacker['user'])
        ->get(route('transactions.attachment', $victim['transaction']))
        ->assertNotFound();
});

it('returns 404 for a transaction without an attachment', function () {
    $data = makeTenantWithTransaction('attachment-missing@example.com');

    actingAs($data['user'])
        ->get(route('transactions.attachment', $data['transaction']))
        ->assertNotFound();
});
