<?php

use App\Console\Commands\SendDueTransactionNotifications;
use App\Http\Middleware\EnsureTenantContext;
use App\Http\Middleware\EnsureTenantSubscribed;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    // Application routes are registered by RouteServiceProvider; only the
    // uptime probe is wired here.
    ->withRouting(
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            EnsureTenantContext::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'subscribed' => EnsureTenantSubscribed::class,
            'verified' => EnsureEmailIsVerified::class,
        ]);

        // The Stripe webhook cannot carry a CSRF token.
        $middleware->validateCsrfTokens(except: [
            'stripe/*',
        ]);

        // Establish the tenant context before route-model binding runs, otherwise
        // SubstituteBindings resolves scoped models with a null tenant and 404s.
        $middleware->prependToPriorityList(
            before: SubstituteBindings::class,
            prepend: EnsureTenantContext::class,
        );
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command(SendDueTransactionNotifications::class)->dailyAt('08:00');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
