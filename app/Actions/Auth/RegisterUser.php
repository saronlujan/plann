<?php

namespace App\Actions\Auth;

use App\Enums\PlanSlug;
use App\Models\Currency;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegisterUser
{
    /**
     * Provision a new tenant workspace and its first user, starting a 14-day
     * card-free trial on the Basic plan.
     *
     * @param  array{name: string, email: string, password: string, locale?: string|null, google_id?: string|null, avatar_url?: string|null, phone?: string|null}  $data
     */
    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $currency = Currency::query()->firstOrCreate(
                ['code' => 'BRL'],
                ['name' => 'Brazilian Real', 'symbol' => 'R$'],
            );

            // The 14-day trial is started automatically by Tenant::booted().
            $tenant = Tenant::query()->create([
                'name' => $data['name'],
                'plan_slug' => PlanSlug::Basic->value,
            ]);

            $tenant->syncCurrencyActivations([$currency->id]);
            $tenant->ensureCurrencyAssets([$currency->id]);

            return User::query()->create([
                'tenant_id' => $tenant->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'google_id' => $data['google_id'] ?? null,
                'avatar_url' => $data['avatar_url'] ?? null,
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'locale' => $data['locale'] ?? 'pt',
            ]);
        });
    }
}
