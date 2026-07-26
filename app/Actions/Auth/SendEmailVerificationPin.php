<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Models\UserPin;
use App\Notifications\EmailVerificationPinNotification;
use Illuminate\Support\Facades\Hash;

class SendEmailVerificationPin
{
    public const EXPIRES_MINUTES = 30;

    public const PURPOSE = 'email_verification';

    /**
     * Issue a fresh 6-digit PIN for the user and email it. Any previous
     * unconsumed verification PINs are invalidated first, so only the newest
     * code ever works.
     */
    public function handle(User $user): void
    {
        $pin = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        UserPin::query()
            ->where('user_id', $user->id)
            ->where('purpose', self::PURPOSE)
            ->whereNull('consumed_at')
            ->delete();

        UserPin::query()->create([
            'user_id' => $user->id,
            'purpose' => self::PURPOSE,
            'pin' => Hash::make($pin),
            'expires_at' => now()->addMinutes(self::EXPIRES_MINUTES),
        ]);

        // Queued notification: fix the locale here or the worker renders the
        // email in the app default language.
        $user->notify(
            (new EmailVerificationPinNotification($pin, self::EXPIRES_MINUTES))
                ->locale($user->locale ?: config('app.locale')),
        );
    }
}
