<?php

namespace App\Models;

use App\Actions\Auth\SendEmailVerificationPin;
use App\Enums\UserColor;
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
 * @property string|null $google_id
 * @property string|null $avatar_url
 * @property string|null $phone
 * @property string $locale
 * @property UserTheme $theme
 * @property UserColor $color
 * @property bool $sound_enabled
 * @property string $sound_theme
 * @property bool $notifications_enabled
 * @property int $notify_days_before
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['tenant_id', 'name', 'email', 'google_id', 'avatar_url', 'phone', 'password', 'locale', 'theme', 'color', 'sound_enabled', 'sound_theme', 'notifications_enabled', 'notify_days_before'])]
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
        'locale' => 'pt',
        'theme' => 'light',
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
            'theme' => UserTheme::class,
            'color' => UserColor::class,
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
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
