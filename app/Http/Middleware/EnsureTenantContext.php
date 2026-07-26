<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantContext = app(TenantContext::class);
        $user = $request->user();

        if ($user === null) {
            $tenantContext->clear();
            $this->syncPostgresTenantContext(null);
            app()->setLocale($this->guestLocale($request));

            return $next($request);
        }

        $tenant = $user->tenant()->first();

        if ($tenant === null) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            app()->setLocale(config('app.locale'));
            abort(403, 'Invalid tenant context.');
        }

        $tenantContext->setTenantId($tenant->id);
        app()->setLocale($user->locale ?: config('app.locale'));
        View::share('appearance', $user->theme?->value ?? 'light');
        $this->syncPostgresTenantContext($tenant->id);

        return $next($request);
    }

    /**
     * Session-selected locale for guests (falls back to the app default).
     */
    private function guestLocale(Request $request): string
    {
        $locale = $request->session()->get('locale');

        return in_array($locale, ['pt', 'es', 'en'], true) ? $locale : config('app.locale');
    }

    private function syncPostgresTenantContext(?int $tenantId): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::selectOne(
            "select set_config('app.tenant_id', ?, false) as tenant_id",
            [$tenantId === null ? '' : (string) $tenantId],
        );
    }
}
