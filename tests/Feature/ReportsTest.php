<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;

use function Pest\Laravel\actingAs;

/**
 * @return array{0: User, 1: Account, 2: Currency}
 */
function reportsFixture(string $email): array
{
    $tenant = Tenant::create(['name' => 'Tenant '.$email]);
    app(TenantContext::class)->setTenantId($tenant->id);

    $user = User::factory()->create(['tenant_id' => $tenant->id, 'email' => $email, 'locale' => 'pt']);

    $currency = Currency::query()->firstOrCreate(['code' => 'BRL'], ['name' => 'Real', 'symbol' => 'R$']);

    $account = Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Conta',
    ]);

    return [$user, $account, $currency];
}

function reportsEntry(Account $account, Currency $currency, string $movement, string $date, float $amount, ?int $categoryId = null, ?string $paidAt = null, bool $isTransfer = false): Transaction
{
    return Transaction::create([
        'tenant_id' => $account->tenant_id,
        'account_id' => $account->id,
        'currency_id' => $currency->id,
        'category_id' => $categoryId,
        'movement_type' => $movement,
        'is_transfer' => $isTransfer,
        'type' => 'unique',
        'effective_date' => $date,
        'paid_at' => $paidAt,
        'amount' => $amount,
        'adjustment_amount' => 0,
        'description' => 'Movimento',
    ]);
}

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('the report defaults to the year to date', function () {
    CarbonImmutable::setTestNow('2026-08-15 10:00:00');

    [$user] = reportsFixture('report-default@example.com');

    actingAs($user)->get(route('reports.index'))->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Reports/Index')
            ->where('ready', true)
            ->where('filters.from', '2026-01')
            ->where('filters.to', '2026-08')
            // January through August inclusive.
            ->has('report.monthly', 8));
});

test('the summary separates realized from expected', function () {
    CarbonImmutable::setTestNow('2026-08-15 10:00:00');

    [$user, $account, $currency] = reportsFixture('report-summary@example.com');

    reportsEntry($account, $currency, 'income', '2026-08-02', 1000, paidAt: '2026-08-02');
    reportsEntry($account, $currency, 'income', '2026-08-20', 500);            // still open
    reportsEntry($account, $currency, 'expense', '2026-08-05', 300, paidAt: '2026-08-05');
    reportsEntry($account, $currency, 'expense', '2026-08-25', 200);           // still open

    actingAs($user)->get(route('reports.index', ['from' => '2026-08', 'to' => '2026-08']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('report.summary.income', '1500.00')
            ->where('report.summary.income_paid', '1000.00')
            ->where('report.summary.expenses', '500.00')
            ->where('report.summary.expenses_paid', '300.00')
            ->where('report.summary.net', '1000.00')
            ->where('report.summary.net_paid', '700.00'));
});

test('transfers are excluded from every total', function () {
    CarbonImmutable::setTestNow('2026-08-15 10:00:00');

    [$user, $account, $currency] = reportsFixture('report-transfer@example.com');

    reportsEntry($account, $currency, 'expense', '2026-08-05', 100, paidAt: '2026-08-05');
    // A transfer moves money between the user's own accounts: counting it would
    // inflate both sides.
    reportsEntry($account, $currency, 'expense', '2026-08-06', 900, paidAt: '2026-08-06', isTransfer: true);
    reportsEntry($account, $currency, 'income', '2026-08-06', 900, paidAt: '2026-08-06', isTransfer: true);

    actingAs($user)->get(route('reports.index', ['from' => '2026-08', 'to' => '2026-08']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('report.summary.expenses', '100.00')
            ->where('report.summary.income', '0.00')
            ->where('report.summary.entries', '1'));
});

test('the category breakdown carries the share of the total', function () {
    CarbonImmutable::setTestNow('2026-08-15 10:00:00');

    [$user, $account, $currency] = reportsFixture('report-category@example.com');

    $market = Category::create([
        'tenant_id' => $account->tenant_id,
        'name' => 'Mercado',
        'type' => 'expense',
        'color' => 'green',
    ]);

    reportsEntry($account, $currency, 'expense', '2026-08-05', 750, $market->id);
    reportsEntry($account, $currency, 'expense', '2026-08-06', 250);  // uncategorised

    actingAs($user)->get(route('reports.index', ['from' => '2026-08', 'to' => '2026-08']))
        ->assertSuccessful()
        ->assertInertia(function ($page) {
            $rows = $page->toArray()['props']['report']['by_category'];

            // Sorted by amount, largest first.
            expect($rows[0]['name'])->toBe('Mercado');
            expect($rows[0]['total'])->toBe('750.00');
            expect($rows[0]['share'])->toBe('75.00');
            expect($rows[1]['name'])->toBe('Sem categoria');
            expect($rows[1]['share'])->toBe('25.00');
        });
});

test('the monthly trend covers every month in the range', function () {
    CarbonImmutable::setTestNow('2026-08-15 10:00:00');

    [$user, $account, $currency] = reportsFixture('report-monthly@example.com');

    reportsEntry($account, $currency, 'income', '2026-06-10', 400);
    reportsEntry($account, $currency, 'expense', '2026-07-10', 100);

    actingAs($user)->get(route('reports.index', ['from' => '2026-06', 'to' => '2026-08']))
        ->assertSuccessful()
        ->assertInertia(function ($page) {
            $months = $page->toArray()['props']['report']['monthly'];

            expect($months)->toHaveCount(3);
            expect(array_column($months, 'month'))->toBe(['2026-06', '2026-07', '2026-08']);
            expect($months[0]['income'])->toBe('400.00');
            expect($months[1]['net'])->toBe('-100.00');
            // A month with no movement still appears, zeroed.
            expect($months[2]['net'])->toBe('0.00');
        });
});

test('an inverted range is reordered instead of returning nothing', function () {
    CarbonImmutable::setTestNow('2026-08-15 10:00:00');

    [$user] = reportsFixture('report-inverted@example.com');

    actingAs($user)->get(route('reports.index', ['from' => '2026-08', 'to' => '2026-06']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('filters.from', '2026-06')
            ->where('filters.to', '2026-08'));
});

test('an oversized range is clamped', function () {
    CarbonImmutable::setTestNow('2026-08-15 10:00:00');

    [$user] = reportsFixture('report-clamp@example.com');

    // Projecting 100 years month by month would be a denial of service.
    actingAs($user)->get(route('reports.index', ['from' => '1926-01', 'to' => '2026-08']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->has('report.monthly', 36));
});

test('the report only counts the selected currency', function () {
    CarbonImmutable::setTestNow('2026-08-15 10:00:00');

    [$user, $account, $brl] = reportsFixture('report-currency@example.com');

    $usd = Currency::query()->firstOrCreate(['code' => 'USD'], ['name' => 'Dollar', 'symbol' => '$']);
    $usdAccount = Account::create([
        'tenant_id' => $account->tenant_id,
        'currency_id' => $usd->id,
        'name' => 'Conta USD',
    ]);

    reportsEntry($account, $brl, 'expense', '2026-08-05', 100);
    reportsEntry($usdAccount, $usd, 'expense', '2026-08-05', 999);

    actingAs($user)->get(route('reports.index', ['from' => '2026-08', 'to' => '2026-08']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->where('report.summary.expenses', '100.00'));
});

test('another tenant transactions never reach the report', function () {
    CarbonImmutable::setTestNow('2026-08-15 10:00:00');

    [, $victimAccount, $currency] = reportsFixture('report-victim@example.com');
    reportsEntry($victimAccount, $currency, 'expense', '2026-08-05', 5000);

    [$attacker] = reportsFixture('report-attacker@example.com');

    actingAs($attacker)->get(route('reports.index', ['from' => '2026-08', 'to' => '2026-08']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->where('report.summary.expenses', '0.00'));
});

test('the report shows an empty state without an active currency', function () {
    $tenant = Tenant::create(['name' => 'Tenant sem moeda']);
    app(TenantContext::class)->setTenantId($tenant->id);
    $user = User::factory()->create(['tenant_id' => $tenant->id, 'email' => 'report-empty@example.com']);

    actingAs($user)->get(route('reports.index'))->assertSuccessful()
        ->assertInertia(fn ($page) => $page->where('ready', false));
});
