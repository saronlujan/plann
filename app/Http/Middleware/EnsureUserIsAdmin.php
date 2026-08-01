<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Guards everything under /admin.
     *
     * A refusal is a flat 403, never a redirect somewhere friendlier: sending
     * someone on would confirm the area exists, and there is nothing there for
     * them to do about it anyway.
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->is_admin === true, 403);

        return $next($request);
    }
}
