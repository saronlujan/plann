<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->routes(function () {
            Route::middleware(['web'])
                ->group(base_path('routes/locale.php'));

            Route::middleware(['web', 'guest'])
                ->group(base_path('routes/login.php'));

            Route::middleware(['web', 'guest'])
                ->group(base_path('routes/password.php'));

            Route::middleware(['web', 'guest'])
                ->group(base_path('routes/google.php'));

            // Signed in but not yet confirmed: the only destination available.
            Route::middleware(['web', 'auth'])
                ->group(base_path('routes/verification.php'));

            Route::middleware(['web', 'auth', 'verified', 'subscribed'])
                ->group(base_path('routes/dashboard.php'));

            Route::middleware(['web', 'auth', 'verified', 'subscribed'])
                ->group(base_path('routes/transactions.php'));

            Route::middleware(['web', 'auth', 'verified', 'subscribed'])
                ->group(base_path('routes/contacts.php'));

            Route::middleware(['web', 'auth', 'verified', 'subscribed'])
                ->group(base_path('routes/accounts.php'));

            Route::middleware(['web', 'auth', 'verified', 'subscribed'])
                ->group(base_path('routes/categories.php'));

            Route::middleware(['web', 'auth', 'verified', 'subscribed'])
                ->group(base_path('routes/tags.php'));

            Route::middleware(['web', 'auth', 'verified', 'subscribed'])
                ->group(base_path('routes/currencies.php'));

            // Billing and preferences stay reachable even when the trial lapses,
            // but still require a confirmed address.
            Route::middleware(['web', 'auth', 'verified'])
                ->group(base_path('routes/billing.php'));

            Route::middleware(['web', 'auth', 'verified'])
                ->group(base_path('routes/preferences.php'));

            Route::middleware(['web', 'auth', 'verified'])
                ->group(base_path('routes/profile.php'));

            // TEMPORARY: dev-only helpers (e.g. force trial expiry) for Stripe testing.
            if (! $this->app->environment('production')) {
                Route::middleware(['web', 'auth'])
                    ->group(base_path('routes/dev.php'));
            }
        });
    }
}
