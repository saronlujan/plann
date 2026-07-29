<?php

namespace App\Models;

use App\Enums\PlanFeature;
use App\Enums\PlanSlug;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'country_id',
        'currency_id',
    ];

    /**
     * Mirrors the column default so a brand-new tenant already has a plan.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'plan_slug' => 'basic',
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
        return Plan::query()->where('slug', $this->plan_slug->value)->first();
    }

    /**
     * Whether the current plan unlocks a given capability.
     */
    public function hasFeature(PlanFeature $feature): bool
    {
        return $this->plan_slug->hasFeature($feature);
    }

    /**
     * Drives country-specific behaviour and the workspace's starting currency.
     *
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * The currencies this workspace uses: the ones it holds an account in.
     *
     * Derived rather than stored. A separate "activated" flag said the same thing
     * as "has an account in it" — every picker already hid currencies without
     * one — and two records of one fact drift.
     *
     * @return BelongsToMany<Currency, $this>
     */
    public function activeCurrencies(): BelongsToMany
    {
        // A plain distinct, not distinct('currencies.id'): Postgres turns the
        // named form into DISTINCT ON, which demands the ORDER BY start with the
        // same expression — and every caller orders by code. Distinct over the
        // whole row is enough here, since the pivot columns repeat per currency.
        //
        // The trade-off is count(): it aggregates over `*`, where the flag does
        // not apply, so count the collection instead of the query.
        return $this->belongsToMany(Currency::class, 'accounts', 'tenant_id', 'currency_id')
            ->distinct();
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<Account, $this>
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }
}
