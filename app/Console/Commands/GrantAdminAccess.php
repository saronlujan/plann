<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * The only way in. `is_admin` is deliberately absent from the model's fillable
 * list, so no form or request can ever grant it — someone with shell access has
 * to say so out loud.
 */
#[Signature('admin:grant {email : The address of the account to promote} {--revoke : Take the access away instead}')]
#[Description('Grant or revoke platform admin access for a user')]
class GrantAdminAccess extends Command
{
    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->components->error(sprintf('No account found for %s.', $email));

            return self::FAILURE;
        }

        $granting = ! $this->option('revoke');

        $user->forceFill(['is_admin' => $granting])->save();

        $this->components->info(sprintf(
            '%s %s admin access.',
            $user->email,
            $granting ? 'now has' : 'no longer has',
        ));

        return self::SUCCESS;
    }
}
