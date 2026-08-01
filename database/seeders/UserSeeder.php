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
     * The three logins worth having on hand: the platform owner, and one
     * customer on each plan so the tiers can be opened side by side.
     *
     * Nothing but the login: no account, no category, no entry. A seeded
     * workspace that already had them would never reach the guided setup, which
     * is exactly what this is here to exercise.
     *
     * Same country and currency throughout: a second currency would only show up
     * on one of them and muddy the comparison.
     */
    public function run(): void
    {
        $workspaces = [
            [
                'email' => 'saronlujan@gmail.com',
                'name' => 'Saron Lujan',
                'tenant' => 'Workspace Admin',
                'plan' => PlanSlug::Pro,
                'admin' => true,
            ],
            [
                'email' => 'pro@gmail.com',
                'name' => 'Cliente Pro',
                'tenant' => 'Workspace Pro',
                'plan' => PlanSlug::Pro,
                'admin' => false,
            ],
            [
                'email' => 'basic@gmail.com',
                'name' => 'Cliente Basic',
                'tenant' => 'Workspace Basic',
                'plan' => PlanSlug::Basic,
                'admin' => false,
            ],
        ];

        $currency = Currency::query()->where('code', 'BRL')->firstOrFail();
        $country = Country::query()->where('code', 'BR')->first();

        foreach ($workspaces as $workspace) {
            $this->seedWorkspace($workspace, $currency, $country);
        }
    }

    /**
     * @param  array{email: string, name: string, tenant: string, plan: PlanSlug, admin: bool}  $workspace
     */
    private function seedWorkspace(array $workspace, Currency $currency, ?Country $country): void
    {
        $user = User::query()->where('email', $workspace['email'])->first();

        // Keyed on the account, never on the workspace name: users.tenant_id is
        // unique, so looking a workspace up by name and handing it to a different
        // address would collide the first time the pairing is rearranged here.
        $tenant = $user === null ? new Tenant : $user->tenant;

        $tenant->fill([
            'name' => $workspace['tenant'],
            'plan_slug' => $workspace['plan']->value,
            'country_id' => $country?->id,
            'currency_id' => $currency->id,
        ]);

        $tenant->trial_ends_at ??= now()->addDays(14);
        $tenant->save();

        $user = User::query()->updateOrCreate(
            ['email' => $workspace['email']],
            [
                'tenant_id' => $tenant->id,
                'name' => $workspace['name'],
                'password' => '12345678',
                'locale' => 'pt',
            ],
        );

        // forceFill, because is_admin is deliberately absent from the model's
        // fillable list — nothing that takes user input may ever set it.
        $user->forceFill(['is_admin' => $workspace['admin']])->save();

        // Verification is skipped so `migrate:fresh --seed` lands straight on the
        // guided setup instead of the confirmation screen.
        $user->markEmailAsVerified();
    }
}
