<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Inertia\Inertia;
use Inertia\Response;

class ShowTenantController extends Controller
{
    /**
     * Who the workspace belongs to and where it stands with billing.
     *
     * Nothing about what is inside it: the admin area exists to answer questions
     * about the account, and a customer's own bookkeeping is theirs. Keeping it
     * out is also what lets this controller stay inside tenant isolation rather
     * than reaching around it.
     */
    public function __invoke(Tenant $tenant): Response
    {
        $tenant->load('users:id,tenant_id,name,email,created_at,email_verified_at');
        $user = $tenant->users->first();

        return Inertia::render('Admin/Tenants/Show', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'plan' => $tenant->plan_slug->label(),
                'subscribed' => $tenant->subscribed(),
                'on_trial' => $tenant->onTrial(),
                'trial_ends_at' => $tenant->trial_ends_at?->toDateString(),
                'created_at' => $tenant->created_at?->toDateString(),
                'stripe_id' => $tenant->stripe_id,
            ],
            'user' => $user === null ? null : [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'verified' => $user->email_verified_at !== null,
                'created_at' => $user->created_at?->toDateString(),
            ],
        ]);
    }
}
