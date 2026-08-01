<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexTenantsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();

        $tenants = Tenant::query()
            ->with('users:id,tenant_id,name,email')
            // Matching on the account's address as well as the workspace name:
            // support arrives knowing an email, not a workspace.
            ->when($search !== '', fn ($query) => $query
                ->where(fn ($inner) => $inner
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhereHas('users', fn ($users) => $users
                        ->where('email', 'like', '%'.$search.'%')
                        ->orWhere('name', 'like', '%'.$search.'%'))))
            ->latest('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Tenant $tenant): array => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'user' => $tenant->users->first()?->name,
                'email' => $tenant->users->first()?->email,
                'plan' => $tenant->plan_slug->label(),
                'status' => $this->status($tenant),
                'created_at' => $tenant->created_at?->toDateString(),
            ]);

        return Inertia::render('Admin/Tenants/Index', [
            'tenants' => $tenants,
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * Where the workspace stands with billing, as one word.
     *
     * The order matters: a paying subscription outranks a trial that has not run
     * out yet, so someone who converted early does not read as still trialling.
     */
    private function status(Tenant $tenant): string
    {
        if ($tenant->subscribed()) {
            return 'subscribed';
        }

        return $tenant->onTrial() ? 'trialing' : 'lapsed';
    }
}
