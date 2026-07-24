<?php

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'locale',
    ];

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
