<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Models\UserPin;
use App\Notifications\PasswordResetPinNotification;
use Illuminate\Support\Facades\Hash;

class SendPasswordResetPin
{
    public const EXPIRES_MINUTES = 10;

    public const PURPOSE = 'password_reset';

    /**
     * Issue a fresh 6-digit PIN for the user and email it. Any previous
     * unconsumed reset PINs are invalidated first.
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

        // The notification is queued, so the worker would otherwise render the
        // email in the app default locale instead of the user's.
        $user->notify(
            (new PasswordResetPinNotification($pin, self::EXPIRES_MINUTES))
                ->locale($user->locale ?: config('app.locale')),
        );
    }
}
