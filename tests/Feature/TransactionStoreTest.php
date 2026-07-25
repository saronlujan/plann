<?php

use App\Enums\TransactionInstallmentFrequency;
use App\Enums\TransactionMovementType;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

it('stores a transaction from the insertion modal', function () {
    Storage::fake('public');

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
            'interest_amount' => 12.5,
            'installment_frequency' => 'bimonthly',
            'installments_total' => 12,
            'installment_number' => 1,
            'attachment' => UploadedFile::fake()->create('contrato.pdf', 100, 'application/pdf'),
        ])
        ->assertRedirect('/transactions?period=2026-12');

    $transaction = Transaction::query()->first();

    expect($transaction?->description)->toBe('Notebook');
    expect($transaction?->series_uuid)->not->toBeEmpty();
    expect($transaction?->tenant_id)->toBe($tenant->id);
    expect($transaction?->movement_type)->toBe(TransactionMovementType::Expense);
    expect($transaction?->installment_frequency)->toBe(TransactionInstallmentFrequency::Bimonthly);
    expect($transaction?->interest_amount)->toBe('12.50');
    expect($transaction?->attachment_path)->not->toBeNull();
    expect(Storage::disk('public')->exists($transaction?->attachment_path ?? ''))->toBeTrue();
});

it('stores a transfer as paired transactions', function () {
    $tenant = Tenant::create([
        'name' => 'Tenant Principal',
    ]);

    $user = User::create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa Teste',
        'email' => 'transfer-transactions@example.com',
        'password' => 'password',
    ]);

    $currency = Currency::create([
        'code' => 'BRL',
        'name' => 'Brazilian Real',
        'symbol' => 'R$',
    ]);

    app(TenantContext::class)->setTenantId($tenant->id);

    $sourceAccount = Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Conta Origem',
        'balance' => 0,
    ]);

    $destinationAccount = Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Conta Destino',
        'balance' => 0,
    ]);

    actingAs($user)
        ->post('/transactions', [
            'movement_type' => 'transfer',
            'type' => 'unique',
            'description' => 'Transferência para reserva',
            'currency_id' => $currency->id,
            'account_id' => $sourceAccount->id,
            'destination_account_id' => $destinationAccount->id,
            'effective_date' => '2026-12-15',
            'amount' => 250,
            'adjustment_amount' => 0,
            'adjustment_month' => null,
            'interest_amount' => 0,
            'effective_until' => null,
        ])
        ->assertRedirect('/transactions?period=2026-12');

    expect(Transaction::query()->count())->toBe(2);
    expect(Transaction::query()->whereNotNull('series_uuid')->count())->toBe(2);
    expect(Transaction::query()->where('movement_type', TransactionMovementType::Expense->value)->exists())->toBeTrue();
    expect(Transaction::query()->where('movement_type', TransactionMovementType::Income->value)->exists())->toBeTrue();
});
