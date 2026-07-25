<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->routes(function () {
            Route::middleware(['web', 'guest'])
                ->group(base_path('routes/login.php'));

            Route::middleware(['web', 'auth'])
                ->group(base_path('routes/dashboard.php'));

            Route::middleware(['web', 'auth'])
                ->group(base_path('routes/transactions.php'));

            Route::middleware(['web', 'auth'])
                ->group(base_path('routes/contacts.php'));

            Route::middleware(['web', 'auth'])
                ->group(base_path('routes/accounts.php'));

            Route::middleware(['web', 'auth'])
                ->group(base_path('routes/settings.php'));

            Route::middleware(['web', 'auth'])
                ->group(base_path('routes/preferences.php'));
        });
    }
}
