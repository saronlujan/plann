<?php

use App\Enums\TransactionInstallmentFrequency;
use App\Enums\TransactionMovementType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Tag;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use App\Support\Transactions\TransactionAttachments;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

it('stores a transaction from the insertion modal', function () {
    Storage::fake('public');

    $tenant = Tenant::create([
        'name' => 'Tenant Principal',
    ]);

    $user = User::factory()->create([
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
    // Receipts live on the private disk, namespaced per tenant.
    expect(Storage::disk(TransactionAttachments::DISK)->exists($transaction?->attachment_path ?? ''))->toBeTrue();
    expect(Storage::disk('public')->exists($transaction?->attachment_path ?? ''))->toBeFalse();
    expect($transaction?->attachment_path)->toStartWith('transactions/attachments/'.$tenant->id.'/');
});

it('stores a transfer as paired transactions', function () {
    $tenant = Tenant::create([
        'name' => 'Tenant Principal',
    ]);

    $user = User::factory()->create([
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

it('stores a transaction with a category and tags', function () {
    Storage::fake('public');

    $tenant = Tenant::create(['name' => 'Tenant Cat']);
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa',
        'email' => 'tx-cat@example.com',
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
    $category = Category::create([
        'tenant_id' => $tenant->id,
        'name' => 'Mercado',
        'type' => 'expense',
        'color' => 'green',
    ]);
    $tagA = Tag::create(['tenant_id' => $tenant->id, 'name' => 'fixo', 'color' => 'blue']);
    $tagB = Tag::create(['tenant_id' => $tenant->id, 'name' => 'casa', 'color' => 'red']);

    actingAs($user)
        ->post('/transactions', [
            'movement_type' => 'expense',
            'type' => 'unique',
            'description' => 'Compra do mês',
            'currency_id' => $currency->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'tags' => [$tagA->id, $tagB->id],
            'effective_date' => '2026-12-15',
            'amount' => 500,
        ])
        ->assertRedirect('/transactions?period=2026-12');

    $transaction = Transaction::query()->firstOrFail();

    expect($transaction->category_id)->toBe($category->id);
    expect($transaction->tags()->pluck('tags.id')->sort()->values()->all())
        ->toBe(collect([$tagA->id, $tagB->id])->sort()->values()->all());
});

it('rejects a category or tag from another tenant', function () {
    $tenant = Tenant::create(['name' => 'Tenant A']);
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa',
        'email' => 'tx-cross@example.com',
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

    $otherTenant = Tenant::create(['name' => 'Tenant B']);
    app(TenantContext::class)->setTenantId($otherTenant->id);
    $foreignCategory = Category::create([
        'tenant_id' => $otherTenant->id,
        'name' => 'Alheia',
        'type' => 'expense',
        'color' => 'green',
    ]);

    app(TenantContext::class)->setTenantId($tenant->id);

    actingAs($user)
        ->post('/transactions', [
            'movement_type' => 'expense',
            'type' => 'unique',
            'description' => 'Teste',
            'currency_id' => $currency->id,
            'account_id' => $account->id,
            'category_id' => $foreignCategory->id,
            'effective_date' => '2026-12-15',
            'amount' => 100,
        ])
        ->assertSessionHasErrors('category_id');
});

it('accepts a dual-use (both) category on income and expense', function () {
    $tenant = Tenant::create(['name' => 'Tenant Both']);
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa',
        'email' => 'tx-both@example.com',
        'password' => 'password',
    ]);
    $currency = Currency::create(['code' => 'BRL', 'name' => 'Real', 'symbol' => 'R$']);
    app(TenantContext::class)->setTenantId($tenant->id);
    $account = Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Conta',
        'balance' => 0,
    ]);
    $both = Category::create([
        'tenant_id' => $tenant->id,
        'name' => 'Hospedagem',
        'type' => 'both',
        'color' => 'blue',
    ]);

    actingAs($user)->post('/transactions', [
        'movement_type' => 'income',
        'type' => 'unique',
        'description' => 'Recebido hospedagem',
        'currency_id' => $currency->id,
        'account_id' => $account->id,
        'category_id' => $both->id,
        'effective_date' => '2026-12-10',
        'amount' => 200,
    ])->assertRedirect();

    actingAs($user)->post('/transactions', [
        'movement_type' => 'expense',
        'type' => 'unique',
        'description' => 'Pago servidor',
        'currency_id' => $currency->id,
        'account_id' => $account->id,
        'category_id' => $both->id,
        'effective_date' => '2026-12-11',
        'amount' => 80,
    ])->assertRedirect();

    expect(Transaction::query()->where('category_id', $both->id)->count())->toBe(2);
});

it('keeps existing transactions when a category type changes', function () {
    $tenant = Tenant::create(['name' => 'Tenant Change']);
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa',
        'email' => 'tx-change@example.com',
        'password' => 'password',
    ]);
    $currency = Currency::create(['code' => 'BRL', 'name' => 'Real', 'symbol' => 'R$']);
    app(TenantContext::class)->setTenantId($tenant->id);
    $account = Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Conta',
        'balance' => 0,
    ]);
    $category = Category::create([
        'tenant_id' => $tenant->id,
        'name' => 'Mercado',
        'type' => 'expense',
        'color' => 'green',
    ]);
    $transaction = Transaction::create([
        'tenant_id' => $tenant->id,
        'account_id' => $account->id,
        'currency_id' => $currency->id,
        'category_id' => $category->id,
        'movement_type' => 'expense',
        'type' => 'unique',
        'effective_date' => '2026-12-10',
        'amount' => 50,
        'adjustment_amount' => 0,
        'description' => 'Compra',
    ]);

    actingAs($user)
        ->patch('/categories/'.$category->id, ['name' => 'Mercado', 'type' => 'income', 'color' => 'green'])
        ->assertRedirect();

    expect($category->fresh()?->type->value)->toBe('income');
    expect($transaction->fresh()?->category_id)->toBe($category->id);
});
