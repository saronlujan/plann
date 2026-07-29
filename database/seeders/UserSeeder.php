<?php

namespace Database\Seeders;

use App\Enums\PlanSlug;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Two workspaces, one per plan, so both tiers can be opened side by side.
     *
     * Nothing but the login: no account, no category, no entry. A seeded
     * workspace that already had them would never reach the guided setup, which
     * is exactly what this is here to exercise.
     *
     * Same country and currency on purpose: the difference under test is the
     * plan, and a second currency would only show up on one of them.
     */
    public function run(): void
    {
        $workspaces = [
            [
                'email' => 'saronlujan@gmail.com',
                'name' => 'Saron Lujan',
                'tenant' => 'Workspace Pro',
                'plan' => PlanSlug::Pro,
            ],
            [
                'email' => 'saronlujan@hotmail.com',
                'name' => 'Saron Lujan',
                'tenant' => 'Workspace Basic',
                'plan' => PlanSlug::Basic,
            ],
        ];

        $currency = Currency::query()->where('code', 'BRL')->firstOrFail();
        $country = Country::query()->where('code', 'BR')->first();

        foreach ($workspaces as $workspace) {
            $this->seedWorkspace($workspace, $currency, $country);
        }
    }

    /**
     * @param  array{email: string, name: string, tenant: string, plan: PlanSlug}  $workspace
     */
    private function seedWorkspace(array $workspace, Currency $currency, ?Country $country): void
    {
        $tenant = Tenant::query()->firstOrCreate(
            ['name' => $workspace['tenant']],
            [
                'plan_slug' => $workspace['plan']->value,
                'country_id' => $country?->id,
                'currency_id' => $currency->id,
                'trial_ends_at' => now()->addDays(14),
            ],
        );

        $user = User::query()->updateOrCreate(
            ['email' => $workspace['email']],
            [
                'tenant_id' => $tenant->id,
                'name' => $workspace['name'],
                'password' => '12345678',
                'locale' => 'pt',
            ],
        );

        // Verification is skipped so `migrate:fresh --seed` lands straight on the
        // guided setup instead of the confirmation screen.
        $user->markEmailAsVerified();
    }
}
