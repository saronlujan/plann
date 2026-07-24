<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        app()->setLocale($tenant->locale ?: config('app.locale'));
        $this->syncPostgresTenantContext($tenant->id);

        return $next($request);
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
