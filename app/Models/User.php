<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserColor;
use App\Enums\UserTheme;
use Database\Factories\UserFactory;
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
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'theme' => UserTheme::class,
            'color' => UserColor::class,
            'sound_enabled' => 'boolean',
            'notifications_enabled' => 'boolean',
            'notify_days_before' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
