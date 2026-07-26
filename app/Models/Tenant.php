<?php

namespace App\Models;

use App\Enums\PlanSlug;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Cashier\Billable;

class Tenant extends Model
{
    use Billable;

    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'plan_slug',
    ];

    protected $casts = [
        'plan_slug' => PlanSlug::class,
        'trial_ends_at' => 'datetime',
    ];

    /**
     * Every new workspace starts on a 14-day, card-free trial.
     */
    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant): void {
            $tenant->trial_ends_at ??= now()->addDays(14);
        });
    }

    /**
     * The plan the tenant is currently on (resolved by slug).
     */
    public function plan(): ?Plan
    {
        return Plan::query()->where('slug', $this->plan_slug?->value ?? PlanSlug::Basic->value)->first();
    }

    /**
     * Maximum number of users allowed for the current plan.
     */
    public function maxUsers(): int
    {
        return ($this->plan_slug ?? PlanSlug::Basic)->maxUsers();
    }

    /**
     * Whether the tenant can still add another user under its plan seat limit.
     */
    public function canAddUser(): bool
    {
        return $this->users()->count() < $this->maxUsers();
    }

    public function currencies(): BelongsToMany
    {
        return $this->belongsToMany(Currency::class)
            ->withPivot('is_active')
            ->withTimestamps();
    }

    public function activeCurrencies(): BelongsToMany
    {
        return $this->currencies()->wherePivot('is_active', true);
    }

    /**
     * @param  array<int, int|string>  $currencyIds
     */
    public function syncCurrencyActivations(array $currencyIds): void
    {
        $activeCurrencyIds = collect($currencyIds)
            ->map(fn (int|string $currencyId): int => (int) $currencyId)
            ->unique()
            ->values()
            ->all();

        $syncData = Currency::query()
            ->orderBy('code')
            ->pluck('id')
            ->mapWithKeys(fn (int $currencyId): array => [
                $currencyId => ['is_active' => in_array($currencyId, $activeCurrencyIds, true)],
            ])
            ->all();

        $this->currencies()->sync($syncData);
    }

    /**
     * @param  array<int, int>  $currencyIds
     */
    public function ensureCurrencyAssets(array $currencyIds): void
    {
        Currency::query()
            ->whereIn('id', $currencyIds)
            ->orderBy('code')
            ->get()
            ->each(function (Currency $currency): void {
                $this->accounts()->updateOrCreate(
                    [
                        'currency_id' => $currency->id,
                        'name' => sprintf('%s account', $currency->code),
                    ],
                    [
                        'balance' => 0,
                    ],
                );
            });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }
}
