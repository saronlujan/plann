<?php

use App\Models\Account;
use App\Models\Currency;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

/**
 * @return array{0: User, 1: Currency, 2: Tenant}
 */
function cardFixture(string $email): array
{
    $tenant = Tenant::create(['name' => 'Tenant '.$email]);
    app(TenantContext::class)->setTenantId($tenant->id);

    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa',
        'email' => $email,
        'password' => 'password',
        'locale' => 'pt',
    ]);

    $currency = Currency::query()->firstOrCreate(['code' => 'BRL'], ['name' => 'Real', 'symbol' => 'R$']);

    return [$user, $currency, $tenant];
}

function creditCard(Tenant $tenant, Currency $currency): Account
{
    return Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Cartão Nubank',
        'kind' => 'credit_card',
        'credit_limit' => 1000,
        'closing_day' => 20,
        'due_day' => 28,
    ]);
}

function cardPurchase(Account $card, Currency $currency, string $date, float $amount): Transaction
{
    return Transaction::create([
        'tenant_id' => $card->tenant_id,
        'account_id' => $card->id,
        'currency_id' => $currency->id,
        'movement_type' => 'expense',
        'type' => 'unique',
        'effective_date' => $date,
        'amount' => $amount,
        'adjustment_amount' => 0,
        'description' => 'Compra',
    ]);
}

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('a credit card can be created through settings with card fields', function () {
    [$user, $currency] = cardFixture('card-create@example.com');

    actingAs($user)->post('/accounts', [
        'name' => 'Cartão',
        'kind' => 'credit_card',
        'currency_id' => (string) $currency->id,
        'credit_limit' => '1500',
        'closing_day' => '20',
        'due_day' => '28',
    ])->assertRedirect();

    $card = Account::query()->where('name', 'Cartão')->firstOrFail();

    expect($card->isCreditCard())->toBeTrue()
        ->and((float) $card->credit_limit)->toBe(1500.0)
        ->and($card->closing_day)->toBe(20)
        ->and($card->due_day)->toBe(28)
        ->and((float) $card->balance)->toBe(0.0);
});

test('the accounts list shows invoice total, due date and available limit for a card', function () {
    CarbonImmutable::setTestNow('2026-08-15 10:00:00');

    [$user, $currency, $tenant] = cardFixture('card-list@example.com');
    $card = creditCard($tenant, $currency);
    cardPurchase($card, $currency, '2026-08-05', 200);
    cardPurchase($card, $currency, '2026-08-10', 100);

    actingAs($user)->get('/accounts')->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Accounts/Index')
            ->where('accounts.0.kind', 'credit_card')
            ->where('accounts.0.invoice_total', '300.00')
            ->where('accounts.0.invoice_due_date', '2026-08-28')
            ->where('accounts.0.available', '700.00'));
});

test('the card statement renders the invoice with its current-cycle purchases', function () {
    CarbonImmutable::setTestNow('2026-08-15 10:00:00');

    [$user, $currency, $tenant] = cardFixture('card-show@example.com');
    $card = creditCard($tenant, $currency);
    cardPurchase($card, $currency, '2026-08-05', 200);

    actingAs($user)->get('/accounts/'.$card->id)->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Accounts/ShowCard')
            ->where('invoice.total', '200.00')
            ->where('invoice.available', '800.00')
            ->where('invoice.due_date', '2026-08-28')
            ->has('entries', 1)
            ->where('entries.0.amount', '200.00'));
});

test('the current cycle excludes purchases from a prior closed cycle', function () {
    CarbonImmutable::setTestNow('2026-08-15 10:00:00');

    [$user, $currency, $tenant] = cardFixture('card-cycle@example.com');
    $card = creditCard($tenant, $currency);
    // Closing day is 20; a purchase on 2026-07-10 belongs to the cycle that closed 2026-07-20.
    cardPurchase($card, $currency, '2026-07-10', 500);
    cardPurchase($card, $currency, '2026-08-05', 200);

    actingAs($user)->get('/accounts/'.$card->id)->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Accounts/ShowCard')
            ->where('invoice.total', '200.00') // only current cycle
            ->where('invoice.outstanding', '700.00') // whole debt
            ->where('invoice.available', '300.00'));
});

test('paying the invoice reduces the outstanding balance via a transfer', function () {
    CarbonImmutable::setTestNow('2026-08-15 10:00:00');

    [$user, $currency, $tenant] = cardFixture('card-pay@example.com');
    $card = creditCard($tenant, $currency);
    $bank = Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Conta',
    ]);

    // Accounts start empty: the money already there is an ordinary entry.
    Transaction::query()->create([
        'tenant_id' => $bank->tenant_id,
        'account_id' => $bank->id,
        'currency_id' => $bank->currency_id,
        'movement_type' => 'income',
        'type' => 'unique',
        'effective_date' => '2026-01-01',
        'paid_at' => '2026-01-01',
        'amount' => 1000,
        'description' => 'Saldo inicial',
    ]);
    cardPurchase($card, $currency, '2026-08-05', 300);

    actingAs($user)->post('/accounts/'.$card->id.'/pay-invoice', [
        'account_id' => (string) $bank->id,
        'amount' => '300',
        'effective_date' => '2026-08-15',
    ])->assertRedirect();

    // Two transfer legs created (bank expense + card income), both is_transfer.
    expect(Transaction::query()->where('is_transfer', true)->count())->toBe(2);

    // The payment is an executed movement, so both legs are already paid.
    expect(Transaction::query()->where('is_transfer', true)->whereNull('paid_at')->count())->toBe(0);

    // Outstanding is now zero: purchase 300 − payment 300.
    actingAs($user)->get('/accounts/'.$card->id)->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->where('invoice.outstanding', '0.00')
            ->where('invoice.available', '1000.00'));
});

test('invoice payment is excluded from the paying account income/expense totals', function () {
    CarbonImmutable::setTestNow('2026-08-15 10:00:00');

    [$user, $currency, $tenant] = cardFixture('card-pay-totals@example.com');
    $card = creditCard($tenant, $currency);
    $bank = Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Conta',
    ]);

    // Accounts start empty: the money already there is an ordinary entry.
    Transaction::query()->create([
        'tenant_id' => $bank->tenant_id,
        'account_id' => $bank->id,
        'currency_id' => $bank->currency_id,
        'movement_type' => 'income',
        'type' => 'unique',
        'effective_date' => '2026-01-01',
        'paid_at' => '2026-01-01',
        'amount' => 1000,
        'description' => 'Saldo inicial',
    ]);
    cardPurchase($card, $currency, '2026-08-05', 300);

    actingAs($user)->post('/accounts/'.$card->id.'/pay-invoice', [
        'account_id' => (string) $bank->id,
        'amount' => '300',
        'effective_date' => '2026-08-15',
    ])->assertRedirect();

    // The bank statement drops by 300 (transfer moves the balance) but the
    // period expense total stays 0 (transfers are not expenses).
    actingAs($user)->get('/accounts/'.$bank->id.'?period=2026-08')->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Accounts/Show')
            ->where('expense', '0.00')
            ->where('closing', '700.00'));
});

test('a bank account in a different currency cannot pay the card invoice', function () {
    CarbonImmutable::setTestNow('2026-08-15 10:00:00');

    [$user, $currency, $tenant] = cardFixture('card-pay-currency@example.com');
    $card = creditCard($tenant, $currency);
    $usd = Currency::query()->firstOrCreate(['code' => 'USD'], ['name' => 'Dollar', 'symbol' => '$']);
    $bank = Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $usd->id,
        'name' => 'Conta USD',
    ]);

    // Accounts start empty: the money already there is an ordinary entry.
    Transaction::query()->create([
        'tenant_id' => $bank->tenant_id,
        'account_id' => $bank->id,
        'currency_id' => $bank->currency_id,
        'movement_type' => 'income',
        'type' => 'unique',
        'effective_date' => '2026-01-01',
        'paid_at' => '2026-01-01',
        'amount' => 1000,
        'description' => 'Saldo inicial',
    ]);

    actingAs($user)->post('/accounts/'.$card->id.'/pay-invoice', [
        'account_id' => (string) $bank->id,
        'amount' => '100',
        'effective_date' => '2026-08-15',
    ])->assertSessionHasErrors('account_id');
});
