<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantSubscribed
{
    /**
     * Allow access while the tenant is on trial or has an active subscription;
     * otherwise send them to the billing page to pick a plan.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->user()?->tenant()->first();

        if ($tenant !== null && ($tenant->subscribed() || $tenant->onTrial())) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(Response::HTTP_PAYMENT_REQUIRED, 'Assinatura necessária.');
        }

        return redirect()->route('billing.index');
    }
}
