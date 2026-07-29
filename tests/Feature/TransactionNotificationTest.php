<?php

use App\Models\Account;
use App\Models\Currency;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\TransactionDueNotification;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

/**
 * @return array{0: Tenant, 1: User, 2: Currency, 3: Account}
 */
function notifyFixture(bool $enabled = true, array $overrides = []): array
{
    $tenant = Tenant::create(['name' => 'Tenant Notify '.uniqid()]);
    app(TenantContext::class)->setTenantId($tenant->id);

    $user = User::factory()->create(array_merge([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa',
        'email' => 'notify-'.uniqid().'@example.com',
        'password' => 'password',
        'locale' => 'pt',
        'phone' => '+5511999999999',
        'notifications_enabled' => $enabled,
        'notify_days_before' => 3,
    ], $overrides));

    $currency = Currency::create(['code' => 'BRL', 'name' => 'Real', 'symbol' => 'R$']);
    $account = Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Conta',
    ]);

    return [$tenant, $user, $currency, $account];
}

function makeTransaction(Tenant $tenant, Account $account, Currency $currency, string $date, ?string $paidAt = null): Transaction
{
    return Transaction::create([
        'tenant_id' => $tenant->id,
        'account_id' => $account->id,
        'currency_id' => $currency->id,
        'movement_type' => 'expense',
        'type' => 'unique',
        'effective_date' => $date,
        'paid_at' => $paidAt,
        'amount' => 100,
        'adjustment_amount' => 0,
        'description' => 'Aluguel',
    ]);
}

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('notifies about transactions due today and coming due', function () {
    CarbonImmutable::setTestNow('2026-08-10 09:00:00');
    Notification::fake();

    [$tenant, $user, $currency, $account] = notifyFixture();

    makeTransaction($tenant, $account, $currency, '2026-08-10');            // due today
    makeTransaction($tenant, $account, $currency, '2026-08-13');            // upcoming (+3 days)
    makeTransaction($tenant, $account, $currency, '2026-08-10', '2026-08-10'); // paid → skipped

    app(TenantContext::class)->clear();

    $this->artisan('app:send-due-transaction-notifications')->assertSuccessful();

    Notification::assertSentTo($user, TransactionDueNotification::class, fn ($n): bool => $n->kind === 'due_today');
    Notification::assertSentTo($user, TransactionDueNotification::class, fn ($n): bool => $n->kind === 'upcoming');
    Notification::assertSentToTimes($user, TransactionDueNotification::class, 2);
});

test('does not notify the same occurrence twice', function () {
    CarbonImmutable::setTestNow('2026-08-10 09:00:00');
    Notification::fake();

    [$tenant, $user, $currency, $account] = notifyFixture();
    makeTransaction($tenant, $account, $currency, '2026-08-10');
    app(TenantContext::class)->clear();

    $this->artisan('app:send-due-transaction-notifications');
    $this->artisan('app:send-due-transaction-notifications');

    Notification::assertSentToTimes($user, TransactionDueNotification::class, 1);
});

test('sends over the mail channel', function () {
    CarbonImmutable::setTestNow('2026-08-10 09:00:00');
    Notification::fake();

    [$tenant, $user, $currency, $account] = notifyFixture();
    makeTransaction($tenant, $account, $currency, '2026-08-10');
    app(TenantContext::class)->clear();

    $this->artisan('app:send-due-transaction-notifications');

    Notification::assertSentTo(
        $user,
        TransactionDueNotification::class,
        fn ($notification, array $channels): bool => $channels === ['mail'],
    );
});

test('users without notifications enabled are skipped', function () {
    CarbonImmutable::setTestNow('2026-08-10 09:00:00');
    Notification::fake();

    [$tenant, $user, $currency, $account] = notifyFixture(enabled: false);
    makeTransaction($tenant, $account, $currency, '2026-08-10');
    app(TenantContext::class)->clear();

    $this->artisan('app:send-due-transaction-notifications');

    Notification::assertNothingSent();
});

test('notifies about overdue unpaid transactions', function () {
    CarbonImmutable::setTestNow('2026-08-10 09:00:00');
    Notification::fake();

    [$tenant, $user, $currency, $account] = notifyFixture();

    makeTransaction($tenant, $account, $currency, '2026-08-05');              // overdue
    makeTransaction($tenant, $account, $currency, '2026-07-28');              // overdue, previous month
    makeTransaction($tenant, $account, $currency, '2026-08-01', '2026-08-02'); // overdue but paid → skipped

    app(TenantContext::class)->clear();

    $this->artisan('app:send-due-transaction-notifications')->assertSuccessful();

    Notification::assertSentTo($user, TransactionDueNotification::class, function ($notification): bool {
        if ($notification->kind !== 'overdue') {
            return false;
        }

        $dates = array_column($notification->items, 'date');

        // Oldest first, and the paid one is absent.
        return count($notification->items) === 2
            && $dates === ['28/07/2026', '05/08/2026'];
    });
});

test('an entry due today is not also reported as overdue', function () {
    CarbonImmutable::setTestNow('2026-08-10 09:00:00');
    Notification::fake();

    [$tenant, $user, $currency, $account] = notifyFixture();
    makeTransaction($tenant, $account, $currency, '2026-08-10');
    app(TenantContext::class)->clear();

    $this->artisan('app:send-due-transaction-notifications');

    Notification::assertSentTo($user, TransactionDueNotification::class, fn ($n): bool => $n->kind === 'due_today');
    Notification::assertSentToTimes($user, TransactionDueNotification::class, 1);
});

test('does not report the same overdue occurrence twice', function () {
    CarbonImmutable::setTestNow('2026-08-10 09:00:00');
    Notification::fake();

    [$tenant, $user, $currency, $account] = notifyFixture();
    makeTransaction($tenant, $account, $currency, '2026-08-05');
    app(TenantContext::class)->clear();

    $this->artisan('app:send-due-transaction-notifications');
    $this->artisan('app:send-due-transaction-notifications');

    Notification::assertSentToTimes($user, TransactionDueNotification::class, 1);
});

test('overdue entries beyond the lookback window are ignored', function () {
    CarbonImmutable::setTestNow('2026-08-10 09:00:00');
    Notification::fake();

    [$tenant, $user, $currency, $account] = notifyFixture();

    makeTransaction($tenant, $account, $currency, '2026-01-15'); // ~7 months late
    app(TenantContext::class)->clear();

    $this->artisan('app:send-due-transaction-notifications');

    Notification::assertNothingSent();
});

test('the due notification is queued and localized for the user', function () {
    CarbonImmutable::setTestNow('2026-08-10 09:00:00');
    Notification::fake();

    [$tenant, $user, $currency, $account] = notifyFixture(overrides: ['locale' => 'es']);
    makeTransaction($tenant, $account, $currency, '2026-08-10');
    app(TenantContext::class)->clear();

    $this->artisan('app:send-due-transaction-notifications');

    Notification::assertSentTo(
        $user,
        TransactionDueNotification::class,
        fn ($notification): bool => $notification instanceof ShouldQueue && $notification->locale === 'es',
    );
});
test('notify_days_before of zero does not send the same entries twice', function () {
    CarbonImmutable::setTestNow('2026-08-10 09:00:00');
    Notification::fake();

    // The "upcoming" horizon collapses onto today; only due_today should fire.
    [$tenant, $user, $currency, $account] = notifyFixture(overrides: ['notify_days_before' => 0]);
    makeTransaction($tenant, $account, $currency, '2026-08-10');
    app(TenantContext::class)->clear();

    $this->artisan('app:send-due-transaction-notifications');

    Notification::assertSentTo($user, TransactionDueNotification::class, fn ($n): bool => $n->kind === 'due_today');
    Notification::assertSentToTimes($user, TransactionDueNotification::class, 1);
});
