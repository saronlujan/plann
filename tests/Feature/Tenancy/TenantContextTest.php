<?php

use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

test('authenticated requests set the postgres tenant context', function () {
    $tenant = Tenant::create([
        'name' => 'Tenant Principal',
    ]);

    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa Teste',
        'email' => 'tenant-context@example.com',
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($user)
        ->get(route('accounts'))
        ->assertSuccessful();

    expect(app(TenantContext::class)->tenantId())->toBe($tenant->id);

    if (DB::connection()->getDriverName() === 'pgsql') {
        $tenantId = DB::selectOne("select current_setting('app.tenant_id', true) as tenant_id")->tenant_id;

        expect($tenantId)->toBe((string) $tenant->id);
    }
});

test('guest requests clear the postgres tenant context', function () {
    $this->get('/login')->assertSuccessful();

    expect(app(TenantContext::class)->tenantId())->toBeNull();

    if (DB::connection()->getDriverName() === 'pgsql') {
        $tenantId = DB::selectOne("select current_setting('app.tenant_id', true) as tenant_id")->tenant_id;

        expect($tenantId)->toBe('');
    }
});
