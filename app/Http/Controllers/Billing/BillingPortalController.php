<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BillingPortalController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $tenant = $request->user()?->tenant()->firstOrFail();

        abort_unless($tenant->hasStripeId(), 404);

        return $tenant->redirectToBillingPortal(route('billing.index'));
    }
}
