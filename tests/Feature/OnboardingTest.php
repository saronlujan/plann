<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Currency;
use App\Models\Tag;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

/**
 * @return array{0: User, 1: Tenant, 2: Currency}
 */
function onboardingFixture(string $email): array
{
    $currency = Currency::query()->firstOrCreate(['code' => 'BRL'], ['name' => 'Real', 'symbol' => 'R$']);

    $tenant = Tenant::create(['name' => 'Tenant '.$email, 'currency_id' => $currency->id]);

    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa Teste',
        'email' => $email,
        'password' => 'password',
    ]);

    app(TenantContext::class)->setTenantId($tenant->id);

    return [$user, $tenant, $currency];
}

it('sends a workspace with no account to the guided setup', function () {
    [$user] = onboardingFixture('onboarding-redirect@example.com');

    // An empty dashboard offers nothing the user can act on from there.
    actingAs($user)->get(route('dashboard'))->assertRedirect(route('onboarding'));
});

it('leaves the dashboard alone once an account exists', function () {
    [$user, $tenant, $currency] = onboardingFixture('onboarding-done@example.com');

    Account::create(['tenant_id' => $tenant->id, 'currency_id' => $currency->id, 'name' => 'Conta']);

    actingAs($user)->get(route('dashboard'))->assertSuccessful();
});

it('opens on the account step for a brand-new workspace', function () {
    [$user] = onboardingFixture('onboarding-first-step@example.com');

    // The pitch rides on this step rather than owning one of its own: a screen
    // that only greets is a screen the user has to click through.
    actingAs($user)->get(route('onboarding'))->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Onboarding/Index')
            ->where('currentStep', 'account')
            ->where('completed.account', false)
            ->where('plan', 'basic')
            ->where('userName', 'Pessoa Teste'));
});

it('moves to the next step as each one is finished', function () {
    [$user, $tenant, $currency] = onboardingFixture('onboarding-progress@example.com');

    Account::create(['tenant_id' => $tenant->id, 'currency_id' => $currency->id, 'name' => 'Conta']);

    actingAs($user)->get(route('onboarding'))->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page->where('currentStep', 'category'));

    Category::create(['tenant_id' => $tenant->id, 'name' => 'Mercado', 'type' => 'expense', 'color' => 'green']);

    actingAs($user)->get(route('onboarding'))->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page->where('currentStep', 'tag'));

    Tag::create(['tenant_id' => $tenant->id, 'name' => 'fixo', 'color' => 'blue']);

    actingAs($user)->get(route('onboarding'))->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page->where('currentStep', 'contact'));

    Contact::create(['tenant_id' => $tenant->id, 'name' => 'Padaria do Zé', 'type' => 'provider']);

    actingAs($user)->get(route('onboarding'))->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page->where('currentStep', 'transaction'));
});

it('lets an optional step be skipped without finishing it', function () {
    [$user, $tenant, $currency] = onboardingFixture('onboarding-skip@example.com');

    Account::create(['tenant_id' => $tenant->id, 'currency_id' => $currency->id, 'name' => 'Conta']);

    // Categories and tags are organisational: nothing breaks without them.
    actingAs($user)->get(route('onboarding', ['step' => 'transaction']))->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->where('currentStep', 'transaction')
            ->where('completed.category', false));
});

it('ignores a step that does not exist', function () {
    [$user] = onboardingFixture('onboarding-bad-step@example.com');

    actingAs($user)->get(route('onboarding', ['step' => 'whatever']))->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page->where('currentStep', 'account'));
});

it('creates each record through the modules own endpoints', function () {
    [$user, $tenant, $currency] = onboardingFixture('onboarding-create@example.com');

    // Nothing here has a second set of validation rules to drift from.
    actingAs($user)->post('/accounts', [
        'name' => 'Conta Corrente',
        'kind' => 'account',
        'currency_id' => $currency->id,
    ])->assertSessionHasNoErrors();

    actingAs($user)->post('/categories', [
        'name' => 'Mercado',
        'type' => 'expense',
        'color' => 'green',
    ])->assertSessionHasNoErrors();

    actingAs($user)->post('/tags', ['name' => 'fixo', 'color' => 'blue'])->assertSessionHasNoErrors();

    actingAs($user)->post('/contacts', [
        'name' => 'Padaria do Zé',
        'type' => 'provider',
    ])->assertSessionHasNoErrors();

    $account = Account::query()->firstOrFail();

    actingAs($user)->post('/transactions', [
        'movement_type' => 'expense',
        'type' => 'unique',
        'description' => 'Compra do mês',
        'currency_id' => $currency->id,
        'account_id' => $account->id,
        'effective_date' => '2026-07-15',
        'amount' => 250,
        'paid' => true,
    ])->assertSessionHasNoErrors();

    expect(Transaction::query()->count())->toBe(1);

    // With everything in place the dashboard takes over.
    actingAs($user)->get(route('dashboard'))->assertSuccessful();
});

it('offers the transfer type nowhere in the first entry', function () {
    [$user, $tenant, $currency] = onboardingFixture('onboarding-no-transfer@example.com');

    Account::create(['tenant_id' => $tenant->id, 'currency_id' => $currency->id, 'name' => 'Conta']);

    // A transfer needs two accounts, and a brand-new workspace has one.
    actingAs($user)->get(route('onboarding', ['step' => 'transaction']))->assertSuccessful()
        ->assertInertia(function (Assert $page): void {
            $types = collect($page->toArray()['props']['movementTypeOptions'])->pluck('value');

            expect($types)->not->toContain('transfer');
        });
});

it('lets the first entry be skipped straight to the dashboard', function () {
    [$user, $tenant, $currency] = onboardingFixture('onboarding-skip-tx@example.com');

    Account::create(['tenant_id' => $tenant->id, 'currency_id' => $currency->id, 'name' => 'Conta']);

    // The account is what the dashboard needs; the first entry is a suggestion,
    // so leaving without it must not send the user back here.
    actingAs($user)->get(route('dashboard'))->assertSuccessful();

    expect(Transaction::query()->exists())->toBeFalse();
});

it('opens the account form on the currency the workspace signed up with', function () {
    [$user, $tenant] = onboardingFixture('onboarding-default-currency@example.com');

    // Pro sees the whole catalogue, which sorts ARS first — the signup currency
    // has to win, or a Brazilian workspace opens on Argentine pesos.
    $tenant->update(['plan_slug' => 'pro']);
    Currency::query()->firstOrCreate(['code' => 'ARS'], ['name' => 'Peso', 'symbol' => '$']);

    actingAs($user)->get(route('onboarding'))->assertSuccessful()
        ->assertInertia(function (Assert $page) use ($tenant): void {
            $props = $page->toArray()['props'];

            expect($props['currencyOptions'][0]['code'])->toBe('ARS');
            expect($props['defaultCurrencyId'])->toBe((string) $tenant->currency_id);
        });
});

it('falls back to the first option when the workspace has no signup currency', function () {
    [$user, $tenant] = onboardingFixture('onboarding-no-signup-currency@example.com');

    // The Google callback can create a workspace without one.
    $tenant->update(['currency_id' => null]);

    actingAs($user)->get(route('onboarding'))->assertSuccessful()
        ->assertInertia(function (Assert $page): void {
            $props = $page->toArray()['props'];

            expect($props['defaultCurrencyId'])->toBe($props['currencyOptions'][0]['value'] ?? '');
        });
});

it('carries the schedule and frequency options for the first entry', function () {
    [$user, $tenant, $currency] = onboardingFixture('onboarding-schedule@example.com');

    Account::create(['tenant_id' => $tenant->id, 'currency_id' => $currency->id, 'name' => 'Conta']);

    // The step offers recurrence, and an instalment needs a period to go with it.
    actingAs($user)->get(route('onboarding', ['step' => 'transaction']))->assertSuccessful()
        ->assertInertia(function (Assert $page): void {
            $props = $page->toArray()['props'];

            $schedules = collect($props['scheduleTypeOptions'])->pluck('value');

            expect($schedules)->toContain('unique');
            expect($schedules)->toContain('recurring');
            expect($schedules)->toContain('installment');
            expect($props['frequencyOptions'])->not->toBeEmpty();
        });
});

it('records a recurring first entry as open when it is not paid yet', function () {
    [$user, $tenant, $currency] = onboardingFixture('onboarding-recurring@example.com');

    $account = Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Conta',
    ]);

    actingAs($user)->post('/transactions', [
        'movement_type' => 'expense',
        'type' => 'recurring',
        'description' => 'Aluguel',
        'currency_id' => $currency->id,
        'account_id' => $account->id,
        'effective_date' => '2026-07-05',
        'amount' => 1500,
        'paid' => false,
    ])->assertSessionHasNoErrors();

    $transaction = Transaction::query()->firstOrFail();

    expect($transaction->type->value)->toBe('recurring');
    // Not paid means it counts as expected, never as realised.
    expect($transaction->paid_at)->toBeNull();
});

it('greets each plan with its own copy', function () {
    [$user, $tenant] = onboardingFixture('onboarding-welcome-plan@example.com');

    // The pitch is the one piece of copy that differs by tier, so the page has to
    // carry the plan and the name it interpolates.
    actingAs($user)->get(route('onboarding'))->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page->where('plan', 'basic'));

    $tenant->update(['plan_slug' => 'pro']);

    actingAs($user)->get(route('onboarding'))->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page->where('plan', 'pro'));
});

it('spends no step on a greeting alone', function () {
    [$user] = onboardingFixture('onboarding-welcome-done@example.com');

    // A step with nothing to fill in could never be completed by doing anything,
    // so it was one click of pure ceremony before the real work started.
    actingAs($user)->get(route('onboarding'))->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->missing('completed.welcome')
            ->where('currentStep', 'account'));
});

it('shows what a finished step produced instead of a blank form', function () {
    [$user, $tenant, $currency] = onboardingFixture('onboarding-revisit@example.com');

    Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Conta Corrente',
    ]);

    // Coming back to a finished step used to reopen an empty create form, which
    // either duplicated the record or tripped a unique name on the second try.
    actingAs($user)->get(route('onboarding', ['step' => 'account']))->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->where('currentStep', 'account')
            ->where('completed.account', true)
            ->where('created.account', 'Conta Corrente'));
});

it('names the record of every finished step', function () {
    [$user, $tenant, $currency] = onboardingFixture('onboarding-created-names@example.com');

    $account = Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Conta Corrente',
    ]);
    Category::create([
        'tenant_id' => $tenant->id,
        'name' => 'Mercado',
        'type' => 'expense',
        'color' => 'green',
    ]);
    Tag::create(['tenant_id' => $tenant->id, 'name' => 'fixo', 'color' => 'blue']);
    Contact::create(['tenant_id' => $tenant->id, 'name' => 'Padaria do Zé', 'type' => 'provider']);
    Transaction::query()->create([
        'tenant_id' => $tenant->id,
        'account_id' => $account->id,
        'currency_id' => $currency->id,
        'movement_type' => 'expense',
        'type' => 'unique',
        'effective_date' => '2026-07-10',
        'amount' => 50,
        'description' => 'Compra do mês',
    ]);

    actingAs($user)->get(route('onboarding', ['step' => 'tag']))->assertSuccessful()
        ->assertInertia(function (Assert $page): void {
            $created = $page->toArray()['props']['created'];

            expect($created['account'])->toBe('Conta Corrente');
            expect($created['category'])->toBe('Mercado');
            expect($created['tag'])->toBe('fixo');
            expect($created['contact'])->toBe('Padaria do Zé');
            expect($created['transaction'])->toBe('Compra do mês');
        });
});

it('honours an explicit step even after it is finished', function () {
    [$user, $tenant, $currency] = onboardingFixture('onboarding-explicit-step@example.com');

    Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Conta Corrente',
    ]);

    // This is what makes "go back" and "skip" work: the step named in the URL
    // wins over the first unfinished one.
    actingAs($user)->get(route('onboarding', ['step' => 'account']))->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page->where('currentStep', 'account'));

    // Without one, the first unfinished step wins — which is how saving advances:
    // the modules answer with back(), and that URL names no step.
    actingAs($user)->get(route('onboarding'))->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page->where('currentStep', 'category'));
});

it('offers the contact step only two fields to fill in', function () {
    [$user, $tenant, $currency] = onboardingFixture('onboarding-contact@example.com');

    Account::create(['tenant_id' => $tenant->id, 'currency_id' => $currency->id, 'name' => 'Conta']);

    // A name and what kind of counterparty it is; everything else the contacts
    // module asks for is optional and can wait.
    actingAs($user)->get(route('onboarding', ['step' => 'contact']))->assertSuccessful()
        ->assertInertia(function (Assert $page): void {
            $types = collect($page->toArray()['props']['contactTypeOptions'])->pluck('value');

            expect($types)->toContain('provider');
            expect($types)->toContain('client');
        });

    actingAs($user)->post('/contacts', [
        'name' => 'Padaria do Zé',
        'type' => 'provider',
    ])->assertSessionHasNoErrors();

    expect(Contact::query()->firstOrFail()->type->value)->toBe('provider');
});
