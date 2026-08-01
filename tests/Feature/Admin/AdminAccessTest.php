<?php

use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

/**
 * @return array{0: User, 1: Tenant}
 */
function adminFixture(string $email, bool $isAdmin = false): array
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

    // forceFill, because is_admin is kept out of $fillable on purpose.
    if ($isAdmin) {
        $user->forceFill(['is_admin' => true])->save();
    }

    return [$user, $tenant];
}

test('the admin area is closed to ordinary customers', function () {
    [$user] = adminFixture('customer@example.com');

    foreach (['/admin', '/admin/tenants'] as $url) {
        actingAs($user)->get($url)->assertForbidden();
    }
});

test('the admin area is closed to guests', function () {
    $this->get('/admin')->assertRedirect(route('login'));
});

test('a customer cannot reach another workspace through the admin area', function () {
    [, $victimTenant] = adminFixture('victim-admin@example.com');
    [$attacker] = adminFixture('attacker-admin@example.com');

    actingAs($attacker)->get('/admin/tenants/'.$victimTenant->id)->assertForbidden();
});

test('admin access cannot be granted through the profile form', function () {
    [$user] = adminFixture('escalation@example.com');

    actingAs($user)
        ->patch('/profile', [
            'name' => 'Pessoa',
            'email' => 'escalation@example.com',
            'is_admin' => true,
        ]);

    // Mass assignment must never reach the flag, whatever the request carries.
    expect($user->fresh()?->is_admin)->toBeFalse();
    actingAs($user)->get('/admin')->assertForbidden();
});

test('an admin sees the overview with the platform figures', function () {
    [$admin] = adminFixture('owner@example.com', isAdmin: true);
    adminFixture('client-one@example.com');
    adminFixture('client-two@example.com');

    actingAs($admin)
        ->get('/admin')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Admin/Dashboard')
            // The admin's own workspace counts too: it is a tenant like any other.
            ->where('stats.tenants', 3)
            ->where('stats.subscribers', 0)
            ->where('stats.monthly_revenue_cents', 0)
            ->has('recent', 3));
});

test('an admin lists and opens a customer', function () {
    [$admin] = adminFixture('owner-list@example.com', isAdmin: true);
    [, $customer] = adminFixture('listed@example.com');

    actingAs($admin)
        ->get('/admin/tenants')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Admin/Tenants/Index')
            ->where('tenants.total', 2));

    actingAs($admin)
        ->get('/admin/tenants/'.$customer->id)
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Admin/Tenants/Show')
            ->where('user.email', 'listed@example.com')
            ->where('tenant.plan', 'Basic'));
});

test('the customer search matches on the account email', function () {
    [$admin] = adminFixture('owner-search@example.com', isAdmin: true);
    adminFixture('findme@example.com');
    adminFixture('somebody-else@example.com');

    actingAs($admin)
        ->get('/admin/tenants?search=findme')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->where('tenants.total', 1)
            ->where('tenants.data.0.email', 'findme@example.com'));
});

test('admin access is granted and revoked from the console', function () {
    [$user] = adminFixture('console@example.com');

    $this->artisan('admin:grant', ['email' => 'console@example.com'])->assertSuccessful();
    expect($user->fresh()?->is_admin)->toBeTrue();

    $this->artisan('admin:grant', ['email' => 'console@example.com', '--revoke' => true])->assertSuccessful();
    expect($user->fresh()?->is_admin)->toBeFalse();

    $this->artisan('admin:grant', ['email' => 'nobody@example.com'])->assertFailed();
});

test('the admin flag reaches the header so only an admin is offered the door', function () {
    [$customer] = adminFixture('no-door@example.com');
    [$admin] = adminFixture('has-door@example.com', isAdmin: true);

    // The header decides on auth.user.is_admin; if it stopped being shared, the
    // entry would quietly disappear for the one person who needs it.
    actingAs($customer)
        ->get('/preferences')
        ->assertInertia(fn (Assert $page): Assert => $page->where('auth.user.is_admin', false));

    actingAs($admin)
        ->get('/preferences')
        ->assertInertia(fn (Assert $page): Assert => $page->where('auth.user.is_admin', true));
});
