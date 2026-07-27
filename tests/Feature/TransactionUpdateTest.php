<?php

use App\Enums\TransactionMovementType;
use App\Enums\TransactionType;
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

    $user = User::factory()->create([
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
    expect($transaction->movement_type)->toBe(TransactionMovementType::Income);
    expect($transaction->type)->toBe(TransactionType::Recurring);
    expect($transaction->amount)->toBe('500.00');
});

it('updates only one recurring occurrence when requested', function () {
    $tenant = Tenant::create([
        'name' => 'Tenant Principal',
    ]);

    $user = User::factory()->create([
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

    $user = User::factory()->create([
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

/**
 * @return array{0: User, 1: Currency, 2: Account, 3: Account}
 */
function transferFixture(string $email): array
{
    $tenant = Tenant::create(['name' => 'Tenant '.$email]);

    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa Teste',
        'email' => $email,
        'password' => 'password',
    ]);

    $currency = Currency::create(['code' => 'BRL', 'name' => 'Real', 'symbol' => 'R$']);

    app(TenantContext::class)->setTenantId($tenant->id);

    $source = Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Conta Origem',
        'balance' => 0,
    ]);

    $destination = Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Conta Destino',
        'balance' => 0,
    ]);

    actingAs($user)->post('/transactions', [
        'movement_type' => 'transfer',
        'type' => 'unique',
        'currency_id' => $currency->id,
        'account_id' => $source->id,
        'destination_account_id' => $destination->id,
        'effective_date' => '2026-12-15',
        'amount' => 250,
    ])->assertSessionHasNoErrors();

    return [$user, $currency, $source, $destination];
}

it('turns a transfer leg into a plain income and unpairs both legs', function () {
    [$user, $currency, $source] = transferFixture('transfer-to-income@example.com');

    $leg = Transaction::query()->where('movement_type', TransactionMovementType::Expense->value)->firstOrFail();
    $sibling = Transaction::query()->whereKeyNot($leg->id)->firstOrFail();

    actingAs($user)->patch('/transactions/'.$leg->id, [
        'movement_type' => 'income',
        'type' => 'unique',
        'description' => 'Virou receita',
        'currency_id' => $currency->id,
        'account_id' => $source->id,
        'effective_date' => '2026-12-15',
        'amount' => 250,
    ])->assertSessionHasNoErrors();

    $leg->refresh();
    $sibling->refresh();

    expect($leg->movement_type)->toBe(TransactionMovementType::Income);
    // The flag is what the list reads, so leaving it on kept showing "transfer".
    expect($leg->is_transfer)->toBeFalse();
    expect($leg->series_uuid)->toBeNull();

    // Half a transfer is not a transfer: the other leg stops being one too.
    expect($sibling->is_transfer)->toBeFalse();
    expect($sibling->series_uuid)->toBeNull();

    // Neither movement is deleted — both really happened and affect a balance.
    expect(Transaction::query()->count())->toBe(2);
});

it('keeps the pairing when a transfer is edited without changing its type', function () {
    [$user, $currency, $source] = transferFixture('transfer-amount-edit@example.com');

    $leg = Transaction::query()->where('movement_type', TransactionMovementType::Expense->value)->firstOrFail();
    $seriesUuid = $leg->series_uuid;

    expect($seriesUuid)->not->toBeNull();

    actingAs($user)->patch('/transactions/'.$leg->id, [
        'movement_type' => 'expense',
        'type' => 'unique',
        'description' => 'Transferência',
        'currency_id' => $currency->id,
        'account_id' => $source->id,
        'effective_date' => '2026-12-15',
        'amount' => 300,
    ])->assertSessionHasNoErrors();

    $leg->refresh();

    expect($leg->amount)->toBe('300.00');
    // Correcting the amount used to null the series and orphan the two legs.
    expect($leg->is_transfer)->toBeTrue();
    expect($leg->series_uuid)->toBe($seriesUuid);
});

it('refuses to turn an existing entry into a transfer', function () {
    [$user, $currency, $source, $destination] = transferFixture('entry-to-transfer@example.com');

    $leg = Transaction::query()->where('movement_type', TransactionMovementType::Expense->value)->firstOrFail();

    // An update cannot grow the second leg a transfer needs.
    actingAs($user)->patch('/transactions/'.$leg->id, [
        'movement_type' => 'transfer',
        'type' => 'unique',
        'description' => 'Transferência',
        'currency_id' => $currency->id,
        'account_id' => $source->id,
        'destination_account_id' => $destination->id,
        'effective_date' => '2026-12-15',
        'amount' => 250,
    ])->assertSessionHasErrors('movement_type');

    expect($leg->refresh()->movement_type)->toBe(TransactionMovementType::Expense);
});

it('converts a recurring series into a one-off without leaving an override', function () {
    $tenant = Tenant::create(['name' => 'Tenant Convert']);

    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa Teste',
        'email' => 'recurring-to-unique@example.com',
        'password' => 'password',
    ]);

    $currency = Currency::create(['code' => 'BRL', 'name' => 'Real', 'symbol' => 'R$']);

    app(TenantContext::class)->setTenantId($tenant->id);

    $account = Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Conta BRL',
        'balance' => 0,
    ]);

    $transaction = Transaction::query()->create([
        'tenant_id' => $tenant->id,
        'account_id' => $account->id,
        'currency_id' => $currency->id,
        'movement_type' => 'expense',
        'type' => TransactionType::Recurring->value,
        'series_uuid' => 'series-to-convert',
        'effective_date' => '2026-07-01',
        'amount' => 100,
        'description' => 'Assinatura',
    ]);

    // The scope picker is hidden once the type stops being recurring, so the
    // request must not carry a leftover "only this one".
    actingAs($user)->patch('/transactions/'.$transaction->id, [
        'movement_type' => 'expense',
        'type' => 'unique',
        'description' => 'Assinatura',
        'currency_id' => $currency->id,
        'account_id' => $account->id,
        'effective_date' => '2026-07-01',
        'amount' => 100,
        'recurrence_scope' => 'all',
    ])->assertSessionHasNoErrors();

    $transaction->refresh();

    expect($transaction->type)->toBe(TransactionType::Unique);
    expect($transaction->series_uuid)->toBeNull();
    // An override would have added a second row instead of converting this one.
    expect(Transaction::query()->count())->toBe(1);
});
