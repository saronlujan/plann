<?php

namespace App\Http\Controllers\Register;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Plan;
use App\Support\Registration\ResolveDefaultCountry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function __invoke(Request $request, ResolveDefaultCountry $defaultCountry): Response
    {
        $countries = Country::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->with('currency:id,code')
            ->get(['code', 'name', 'currency_id']);

        return Inertia::render('Register/Register', [
            'googleOAuthEnabled' => $this->isGoogleOAuthEnabled(),
            // Name and description come from the enum, not the plans table: the
            // seeded copies are frozen in whatever locale seeded them, and this
            // page follows the visitor's language.
            'planOptions' => Plan::query()
                ->active()
                ->orderBy('sort_order')
                ->get(['slug', 'monthly_price_cents'])
                ->map(fn (Plan $plan): array => [
                    'value' => $plan->slug->value,
                    'label' => $plan->slug->label(),
                    'description' => $plan->slug->description(),
                    'monthly_price_cents' => $plan->monthly_price_cents,
                    // The headline figure is per month, but the charge is yearly —
                    // the card has to show both or the price reads as misleading.
                    'annual_price_cents' => $plan->annualPriceCents(),
                ])
                ->all(),
            // Alphabetical order would greet every Brazilian with Argentina.
            'defaultCountry' => $defaultCountry->handle($request, $countries),
            'countryOptions' => $countries
                ->map(fn (Country $country): array => [
                    'value' => $country->code,
                    'label' => $country->name,
                    // Lets the form preselect the currency when the country changes.
                    'currency' => $country->currency->code,
                ])
                ->all(),
            // The shared catalogue only: a brand new workspace has no currencies
            // of its own yet.
            'currencyOptions' => Currency::query()
                ->whereNull('tenant_id')
                ->orderBy('code')
                ->get(['code', 'name', 'symbol'])
                ->map(fn (Currency $currency): array => [
                    'value' => $currency->code,
                    'label' => $currency->code.' - '.$currency->name,
                ])
                ->all(),
        ]);
    }

    private function isGoogleOAuthEnabled(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }
}
