<?php

namespace App\Actions\Auth;

use App\Enums\PlanSlug;
use App\Models\Country;
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
     * @param  array{name: string, email: string, password: string, country_code?: string|null, currency_code?: string|null, locale?: string|null, google_id?: string|null, avatar_url?: string|null, phone?: string|null}  $data
     */
    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $country = $this->resolveCountry($data['country_code'] ?? null);

            // The 14-day trial is started automatically by Tenant::booted().
            $tenant = Tenant::query()->create([
                'name' => $data['name'],
                'plan_slug' => PlanSlug::Basic->value,
                'country_id' => $country?->id,
            ]);

            // The signup form asks for a currency; the country's own is the fallback
            // for flows that do not (the Google callback).
            //
            // No account is created here on purpose: a workspace that opens with an
            // account nobody asked for — named after a currency code — reads as a
            // bug. The accounts page guides the first one instead.
            $currencyId = $this->resolveCurrencyId($data['currency_code'] ?? null, $country);

            if ($currencyId !== null) {
                $tenant->syncCurrencyActivations([$currencyId]);
            }

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

    /**
     * Only the shared catalogue is offered at signup: the workspace does not exist
     * yet, so it cannot have currencies of its own.
     */
    private function resolveCurrencyId(?string $code, ?Country $country): ?int
    {
        if ($code !== null) {
            $currency = Currency::query()->whereNull('tenant_id')->where('code', $code)->first();

            if ($currency !== null) {
                return $currency->id;
            }
        }

        return $country?->currency_id;
    }

    /**
     * Falls back to the first active country so a signup that omits it (the
     * Google flow) still lands on a usable workspace.
     */
    private function resolveCountry(?string $code): ?Country
    {
        if ($code !== null) {
            $country = Country::query()->where('is_active', true)->where('code', $code)->first();

            if ($country !== null) {
                return $country;
            }
        }

        return Country::query()->where('is_active', true)->orderBy('code')->first();
    }
}
