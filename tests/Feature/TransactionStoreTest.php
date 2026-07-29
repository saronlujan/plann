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
    expect($transaction?->attachment)->not->toBeNull();
    // Only the file name is persisted; the folder is rebuilt from the tenant.
    expect($transaction?->attachment)->not->toContain('/');

    $path = app(TransactionAttachments::class)->path((string) $transaction?->attachment);

    // Receipts live on the private disk, namespaced per tenant.
    expect(Storage::disk(TransactionAttachments::DISK)->exists($path))->toBeTrue();
    expect(Storage::disk('public')->exists($path))->toBeFalse();
    expect($path)->toStartWith('transactions/attachments/'.$tenant->id.'/');
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
    ]);

    $destinationAccount = Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Conta Destino',
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

it('names a transfer automatically when no description is given', function () {
    $tenant = Tenant::create(['name' => 'Tenant Transfer']);

    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa Teste',
        'email' => 'unnamed-transfer@example.com',
        'password' => 'password',
        'locale' => 'pt',
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
    ]);

    $destinationAccount = Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Conta Destino',
    ]);

    // The list already shows "origin → destination" underneath, so naming the
    // transfer adds nothing and is not worth blocking the form over.
    actingAs($user)
        ->post('/transactions', [
            'movement_type' => 'transfer',
            'type' => 'unique',
            'description' => '',
            'currency_id' => $currency->id,
            'account_id' => $sourceAccount->id,
            'destination_account_id' => $destinationAccount->id,
            'effective_date' => '2026-12-15',
            'amount' => 250,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/transactions?period=2026-12');

    $descriptions = Transaction::query()->pluck('description');

    expect($descriptions)->toHaveCount(2);
    expect($descriptions->unique()->all())->toBe(['Transferência']);
});

it('keeps the description when a transfer is named', function () {
    $tenant = Tenant::create(['name' => 'Tenant Named Transfer']);

    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa Teste',
        'email' => 'named-transfer@example.com',
        'password' => 'password',
    ]);

    $currency = Currency::create(['code' => 'BRL', 'name' => 'Real', 'symbol' => 'R$']);

    app(TenantContext::class)->setTenantId($tenant->id);

    $sourceAccount = Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Conta Origem',
    ]);

    $destinationAccount = Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Conta Destino',
    ]);

    actingAs($user)
        ->post('/transactions', [
            'movement_type' => 'transfer',
            'type' => 'unique',
            'description' => 'Guardar para a reserva',
            'currency_id' => $currency->id,
            'account_id' => $sourceAccount->id,
            'destination_account_id' => $destinationAccount->id,
            'effective_date' => '2026-12-15',
            'amount' => 250,
        ])
        ->assertSessionHasNoErrors();

    expect(Transaction::query()->pluck('description')->unique()->all())->toBe(['Guardar para a reserva']);
});

it('still requires a description outside transfers', function () {
    $tenant = Tenant::create(['name' => 'Tenant Expense']);

    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa Teste',
        'email' => 'unnamed-expense@example.com',
        'password' => 'password',
    ]);

    $currency = Currency::create(['code' => 'BRL', 'name' => 'Real', 'symbol' => 'R$']);

    app(TenantContext::class)->setTenantId($tenant->id);

    $account = Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Conta',
    ]);

    actingAs($user)
        ->post('/transactions', [
            'movement_type' => 'expense',
            'type' => 'unique',
            'description' => '',
            'currency_id' => $currency->id,
            'account_id' => $account->id,
            'effective_date' => '2026-12-15',
            'amount' => 250,
        ])
        ->assertSessionHasErrors('description');

    expect(Transaction::query()->count())->toBe(0);
});

it('books a transaction as settled when the form says paid', function () {
    $tenant = Tenant::create(['name' => 'Tenant Pago']);

    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa Teste',
        'email' => 'paid-yes@example.com',
        'password' => 'password',
    ]);

    $currency = Currency::create(['code' => 'BRL', 'name' => 'Real', 'symbol' => 'R$']);

    app(TenantContext::class)->setTenantId($tenant->id);

    $account = Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Conta',
    ]);

    actingAs($user)
        ->post('/transactions', [
            'movement_type' => 'expense',
            'type' => 'unique',
            'description' => 'Mercado',
            'currency_id' => $currency->id,
            'account_id' => $account->id,
            'effective_date' => '2026-12-15',
            'amount' => 100,
            'paid' => true,
        ])
        ->assertSessionHasNoErrors();

    // Settled on its own date, not on today's: the entry belongs to the month
    // the user chose.
    expect(Transaction::query()->value('paid_at')?->toDateString())->toBe('2026-12-15');
});

it('leaves a transaction open when the form says not paid', function () {
    $tenant = Tenant::create(['name' => 'Tenant Aberto']);

    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa Teste',
        'email' => 'paid-no@example.com',
        'password' => 'password',
    ]);

    $currency = Currency::create(['code' => 'BRL', 'name' => 'Real', 'symbol' => 'R$']);

    app(TenantContext::class)->setTenantId($tenant->id);

    $account = Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Conta',
    ]);

    actingAs($user)
        ->post('/transactions', [
            'movement_type' => 'expense',
            'type' => 'unique',
            'description' => 'Boleto',
            'currency_id' => $currency->id,
            'account_id' => $account->id,
            'effective_date' => '2026-12-15',
            'amount' => 100,
            'paid' => false,
        ])
        ->assertSessionHasNoErrors();

    expect(Transaction::query()->value('paid_at'))->toBeNull();
    // An open entry counts as expected, never as realised.
    expect(Transaction::query()->count())->toBe(1);
});

it('keeps the note and the observations it was given', function () {
    $tenant = Tenant::create(['name' => 'Tenant Notas']);

    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa Teste',
        'email' => 'store-notes@example.com',
        'password' => 'password',
    ]);

    $currency = Currency::create(['code' => 'BRL', 'name' => 'Real', 'symbol' => 'R$']);

    app(TenantContext::class)->setTenantId($tenant->id);

    $account = Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Conta BRL',
    ]);

    // Both are free-form and neither is required, so the only thing to prove is
    // that they survive the round trip.
    actingAs($user)
        ->post('/transactions', [
            'movement_type' => 'expense',
            'type' => 'unique',
            'description' => 'Notebook',
            'note' => 'Pedido 4821',
            'observations' => 'Garantia de 12 meses, retirar nota com o fornecedor.',
            'currency_id' => $currency->id,
            'account_id' => $account->id,
            'effective_date' => '2026-12-15',
            'amount' => 3600,
        ])
        ->assertSessionHasNoErrors();

    $transaction = Transaction::query()->firstOrFail();

    expect($transaction->note)->toBe('Pedido 4821');
    expect($transaction->observations)->toBe('Garantia de 12 meses, retirar nota com o fornecedor.');
});

it('leaves the note and the observations empty when they are not filled in', function () {
    $tenant = Tenant::create(['name' => 'Tenant Sem Notas']);

    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa Teste',
        'email' => 'store-no-notes@example.com',
        'password' => 'password',
    ]);

    $currency = Currency::create(['code' => 'BRL', 'name' => 'Real', 'symbol' => 'R$']);

    app(TenantContext::class)->setTenantId($tenant->id);

    $account = Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Conta BRL',
    ]);

    actingAs($user)
        ->post('/transactions', [
            'movement_type' => 'expense',
            'type' => 'unique',
            'description' => 'Notebook',
            'currency_id' => $currency->id,
            'account_id' => $account->id,
            'effective_date' => '2026-12-15',
            'amount' => 3600,
        ])
        ->assertSessionHasNoErrors();

    $transaction = Transaction::query()->firstOrFail();

    expect($transaction->note)->toBeNull();
    expect($transaction->observations)->toBeNull();
});
