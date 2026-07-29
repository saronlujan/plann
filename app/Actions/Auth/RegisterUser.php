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
     * @param  array{name: string, email: string, password: string, plan_slug?: string|null, country_code?: string|null, currency_code?: string|null, locale?: string|null, google_id?: string|null, avatar_url?: string|null, phone?: string|null}  $data
     */
    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $country = $this->resolveCountry($data['country_code'] ?? null);
            $currencyId = $this->resolveCurrencyId($data['currency_code'] ?? null, $country);

            // The 14-day trial is started automatically by Tenant::booted(), and
            // runs on the plan chosen at signup — so somebody who picked Pro tries
            // Pro, instead of trialling a tier they did not want.
            $tenant = Tenant::query()->create([
                'name' => $data['name'],
                'plan_slug' => $this->resolvePlan($data['plan_slug'] ?? null)->value,
                'country_id' => $country?->id,
                'currency_id' => $currencyId,
            ]);

            // No account is created here on purpose: a workspace that opens with
            // an account nobody asked for reads as a bug. The accounts page guides
            // the first one, and the currency above is what it will be opened in.

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
     * The currency the workspace is opened with.
     *
     * The signup form asks for it; the country's own is the fallback for flows
     * that do not (the Google callback). Only the shared catalogue is offered:
     * the workspace does not exist yet, so it cannot have currencies of its own.
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
     * The plan chosen at signup, falling back to Basic.
     *
     * The fallback covers flows that never show the picker (the Google callback)
     * and keeps an unknown slug from provisioning a tier nobody paid for.
     */
    private function resolvePlan(?string $slug): PlanSlug
    {
        return PlanSlug::tryFrom((string) $slug) ?? PlanSlug::Basic;
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
