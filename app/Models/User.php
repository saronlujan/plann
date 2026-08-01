<?php

namespace App\Models;

use App\Actions\Auth\SendEmailVerificationPin;
use App\Enums\UserTheme;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property bool $is_admin Runs the platform. Absent from $fillable on purpose.
 * @property string|null $google_id
 * @property string|null $avatar_url
 * @property string|null $avatar
 * @property string|null $phone
 * @property string $locale
 * @property UserTheme $theme
 * @property string $color A palette name, or a hand-picked `#rrggbb`.
 * @property bool $sound_enabled
 * @property string $sound_theme
 * @property bool $notifications_enabled
 * @property int $notify_days_before
 * @property int|null $default_currency_id
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['tenant_id', 'name', 'email', 'google_id', 'avatar_url', 'avatar', 'phone', 'password', 'locale', 'theme', 'color', 'sound_enabled', 'sound_theme', 'notifications_enabled', 'notify_days_before', 'default_currency_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Mirrors the column defaults so a brand-new instance already carries them,
     * instead of exposing null until the row is round-tripped through the DB.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        // The header reads this to decide whether to offer the admin area, and it
        // is shared on every response — absent, the door would simply not appear
        // for the one person who needs it.
        'is_admin' => false,
        'locale' => 'pt',
        // Defer to the device until told otherwise: someone who has set their
        // phone to dark did not ask this app to be the exception.
        'theme' => 'system',
        'color' => 'zinc',
        'sound_enabled' => true,
        'sound_theme' => 'blip',
        'notifications_enabled' => false,
        'notify_days_before' => 3,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
            'is_admin' => 'boolean',
            'theme' => UserTheme::class,
            'sound_enabled' => 'boolean',
            'notifications_enabled' => 'boolean',
            'notify_days_before' => 'integer',
        ];
    }

    /**
     * This app verifies with a short-lived 6-digit PIN instead of Laravel's
     * default signed URL, so the address works even when the user opens the
     * email on a different device from the one they signed up on.
     */
    public function sendEmailVerificationNotification(): void
    {
        app(SendEmailVerificationPin::class)->handle($this);
    }

    /**
     * Preferred currency for new records. Null falls back to the tenant's first
     * active currency.
     *
     * @return BelongsTo<Currency, $this>
     */
    public function defaultCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'default_currency_id');
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
