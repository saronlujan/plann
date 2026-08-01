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
 * @return array{0: User, 1: Account, 2: Currency}
 */
function filterFixture(string $email): array
{
    $tenant = Tenant::create(['name' => 'Tenant '.$email]);
    app(TenantContext::class)->setTenantId($tenant->id);

    $user = User::factory()->create(['tenant_id' => $tenant->id, 'email' => $email, 'locale' => 'pt']);
    $currency = Currency::query()->firstOrCreate(['code' => 'BRL'], ['name' => 'Real', 'symbol' => 'R$']);

    $account = Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Conta BRL',
    ]);

    return [$user, $account, $currency];
}

function filterEntry(Account $account, Currency $currency, string $description, string $date, float $amount, string $movement = 'expense', ?string $paidAt = null): Transaction
{
    return Transaction::create([
        'tenant_id' => $account->tenant_id,
        'account_id' => $account->id,
        'currency_id' => $currency->id,
        'movement_type' => $movement,
        'type' => 'unique',
        'effective_date' => $date,
        'paid_at' => $paidAt,
        'amount' => $amount,
        'description' => $description,
    ]);
}

test('the month is the default stretch', function () {
    CarbonImmutable::setTestNow('2026-08-15');
    [$user, $account, $currency] = filterFixture('scale-default@example.com');

    filterEntry($account, $currency, 'Dentro', '2026-08-02', 100);
    filterEntry($account, $currency, 'Fora', '2026-09-02', 100);

    actingAs($user)->get('/transactions')
        ->assertInertia(fn (Assert $page): Assert => $page
            ->where('filters.scale', 'month')
            ->where('filters.from', '2026-08-01')
            ->where('filters.to', '2026-08-31')
            ->has('entries', 1)
            ->where('entries.0.description', 'Dentro'));

    CarbonImmutable::setTestNow();
});

test('the day scale shows only that day', function () {
    CarbonImmutable::setTestNow('2026-08-15');
    [$user, $account, $currency] = filterFixture('scale-day@example.com');

    filterEntry($account, $currency, 'Hoje', '2026-08-15', 100);
    filterEntry($account, $currency, 'Ontem', '2026-08-14', 100);

    actingAs($user)->get('/transactions?scale=day')
        ->assertInertia(fn (Assert $page): Assert => $page
            ->where('filters.from', '2026-08-15')
            ->where('filters.to', '2026-08-15')
            ->has('entries', 1)
            ->where('entries.0.description', 'Hoje'));

    CarbonImmutable::setTestNow();
});

test('the week scale spans its own seven days', function () {
    CarbonImmutable::setTestNow('2026-08-12'); // A Wednesday.
    [$user, $account, $currency] = filterFixture('scale-week@example.com');

    filterEntry($account, $currency, 'Na semana', '2026-08-13', 100);
    filterEntry($account, $currency, 'Semana seguinte', '2026-08-20', 100);

    actingAs($user)->get('/transactions?scale=week')
        ->assertInertia(fn (Assert $page): Assert => $page
            ->has('entries', 1)
            ->where('entries.0.description', 'Na semana'));

    CarbonImmutable::setTestNow();
});

test('a week straddling two months still collects both sides', function () {
    // The projector expands month by month; a week across the boundary is the
    // case that breaks if only one month is ever projected.
    CarbonImmutable::setTestNow('2026-09-01'); // Tuesday; the week opens in August.
    [$user, $account, $currency] = filterFixture('scale-straddle@example.com');

    filterEntry($account, $currency, 'Agosto', '2026-08-31', 100);
    filterEntry($account, $currency, 'Setembro', '2026-09-02', 100);

    actingAs($user)->get('/transactions?scale=week')
        ->assertInertia(fn (Assert $page): Assert => $page->has('entries', 2));

    CarbonImmutable::setTestNow();
});

test('the arrows step by the scale in force', function () {
    [$user, $account, $currency] = filterFixture('scale-step@example.com');

    filterEntry($account, $currency, 'Julho', '2026-07-10', 100);

    // The front end moves the anchor; the server just has to honour it.
    actingAs($user)->get('/transactions?scale=month&date=2026-07-10')
        ->assertInertia(fn (Assert $page): Assert => $page
            ->where('filters.from', '2026-07-01')
            ->where('filters.to', '2026-07-31')
            ->has('entries', 1));
});

test('an explicit range overrides the scale', function () {
    CarbonImmutable::setTestNow('2026-08-15');
    [$user, $account, $currency] = filterFixture('range@example.com');

    filterEntry($account, $currency, 'Junho', '2026-06-10', 100);
    filterEntry($account, $currency, 'Agosto', '2026-08-10', 100);

    actingAs($user)->get('/transactions?scale=day&from=2026-06-01&to=2026-08-31')
        ->assertInertia(fn (Assert $page): Assert => $page
            ->where('filters.custom_range', true)
            ->has('entries', 2));

    CarbonImmutable::setTestNow();
});

test('a range given backwards is read as the stretch between the two dates', function () {
    CarbonImmutable::setTestNow('2026-08-15');
    [$user, $account, $currency] = filterFixture('range-backwards@example.com');

    filterEntry($account, $currency, 'Julho', '2026-07-10', 100);

    actingAs($user)->get('/transactions?from=2026-08-31&to=2026-06-01')
        ->assertInertia(fn (Assert $page): Assert => $page
            ->where('filters.from', '2026-06-01')
            ->where('filters.to', '2026-08-31')
            ->has('entries', 1));

    CarbonImmutable::setTestNow();
});

test('the search matches description, note and account', function () {
    CarbonImmutable::setTestNow('2026-08-15');
    [$user, $account, $currency] = filterFixture('search@example.com');

    filterEntry($account, $currency, 'Mercado do bairro', '2026-08-02', 100);
    $withNote = filterEntry($account, $currency, 'Compra', '2026-08-03', 100);
    $withNote->update(['note' => 'pedido 4471']);
    filterEntry($account, $currency, 'Aluguel', '2026-08-04', 100);

    actingAs($user)->get('/transactions?search=mercado')
        ->assertInertia(fn (Assert $page): Assert => $page->has('entries', 1));

    // Case does not matter, and the note is searched too.
    actingAs($user)->get('/transactions?search=PEDIDO+4471')
        ->assertInertia(fn (Assert $page): Assert => $page
            ->has('entries', 1)
            ->where('entries.0.description', 'Compra'));

    // The account name is the third thing people remember.
    actingAs($user)->get('/transactions?search=conta brl')
        ->assertInertia(fn (Assert $page): Assert => $page->has('entries', 3));

    CarbonImmutable::setTestNow();
});

test('entries filter by movement and by whether they are settled', function () {
    CarbonImmutable::setTestNow('2026-08-15');
    [$user, $account, $currency] = filterFixture('movement@example.com');

    filterEntry($account, $currency, 'Salário', '2026-08-02', 500, 'income', '2026-08-02');
    filterEntry($account, $currency, 'Aluguel', '2026-08-05', 300, 'expense');
    filterEntry($account, $currency, 'Mercado', '2026-08-06', 200, 'expense', '2026-08-06');

    actingAs($user)->get('/transactions?movement=income')
        ->assertInertia(fn (Assert $page): Assert => $page->has('entries', 1));

    actingAs($user)->get('/transactions?status=pending')
        ->assertInertia(fn (Assert $page): Assert => $page
            ->has('entries', 1)
            ->where('entries.0.description', 'Aluguel'));

    actingAs($user)->get('/transactions?movement=expense&status=paid')
        ->assertInertia(fn (Assert $page): Assert => $page
            ->has('entries', 1)
            ->where('entries.0.description', 'Mercado'));

    CarbonImmutable::setTestNow();
});

test('entries can be ordered by date or by amount', function () {
    CarbonImmutable::setTestNow('2026-08-15');
    [$user, $account, $currency] = filterFixture('order@example.com');

    filterEntry($account, $currency, 'Pequena', '2026-08-02', 10);
    filterEntry($account, $currency, 'Grande', '2026-08-03', 900);
    filterEntry($account, $currency, 'Média', '2026-08-04', 400);

    actingAs($user)->get('/transactions')
        ->assertInertia(fn (Assert $page): Assert => $page->where('entries.0.description', 'Média'));

    actingAs($user)->get('/transactions?order=date_asc')
        ->assertInertia(fn (Assert $page): Assert => $page->where('entries.0.description', 'Pequena'));

    actingAs($user)->get('/transactions?order=amount_desc')
        ->assertInertia(fn (Assert $page): Assert => $page->where('entries.0.description', 'Grande'));

    actingAs($user)->get('/transactions?order=amount_asc')
        ->assertInertia(fn (Assert $page): Assert => $page->where('entries.0.description', 'Pequena'));

    CarbonImmutable::setTestNow();
});

test('a nonsense filter falls back instead of erroring', function () {
    CarbonImmutable::setTestNow('2026-08-15');
    [$user, $account, $currency] = filterFixture('garbage@example.com');

    filterEntry($account, $currency, 'Agosto', '2026-08-02', 100);

    // A stale bookmark must not produce an error page.
    actingAs($user)->get('/transactions?scale=fortnight&order=whatever&date=not-a-date')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->where('filters.scale', 'month')
            ->where('filters.order', 'date_desc')
            ->has('entries', 1));

    CarbonImmutable::setTestNow();
});

test('the month links already out there still work', function () {
    [$user, $account, $currency] = filterFixture('legacy-period@example.com');

    filterEntry($account, $currency, 'Maio', '2026-05-10', 100);

    actingAs($user)->get('/transactions?period=2026-05')
        ->assertInertia(fn (Assert $page): Assert => $page
            ->where('filters.from', '2026-05-01')
            ->has('entries', 1));
});

test('entries can be ordered by name from the column header', function () {
    CarbonImmutable::setTestNow('2026-08-15');
    [$user, $account, $currency] = filterFixture('order-name@example.com');

    filterEntry($account, $currency, 'zebra', '2026-08-02', 10);
    filterEntry($account, $currency, 'Aluguel', '2026-08-03', 900);
    filterEntry($account, $currency, 'mercado', '2026-08-04', 400);

    // Lowercase folded, or every capitalised description would sort ahead of
    // every lowercase one instead of alphabetically.
    actingAs($user)->get('/transactions?order=name_asc')
        ->assertInertia(fn (Assert $page): Assert => $page
            ->where('entries.0.description', 'Aluguel')
            ->where('entries.2.description', 'zebra'));

    actingAs($user)->get('/transactions?order=name_desc')
        ->assertInertia(fn (Assert $page): Assert => $page
            ->where('entries.0.description', 'zebra'));

    CarbonImmutable::setTestNow();
});
