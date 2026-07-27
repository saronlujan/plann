<?php

namespace App\Models;

use App\Models\Scopes\CurrencyCatalogScope;
use Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $tenant_id
 * @property string $name
 * @property string $code
 * @property string $symbol
 * @property int|null $accounts_count
 */
#[ScopedBy(CurrencyCatalogScope::class)]
class Currency extends Model
{
    /** @use HasFactory<CurrencyFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'symbol',
    ];

    /**
     * Part of the shared catalogue, rather than added by one workspace.
     */
    public function isGlobal(): bool
    {
        return $this->tenant_id === null;
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsToMany<Tenant, $this>
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class)
            ->withPivot('is_active')
            ->withTimestamps();
    }

    /**
     * @return HasMany<Account, $this>
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    /**
     * Restricts to currencies the workspace can actually transact in.
     *
     * An activated currency with no account is dead weight in a picker: choosing
     * it leaves the account select empty and the form impossible to submit.
     * Accounts are tenant-scoped globally, so this needs no tenant argument.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeUsable(Builder $query): void
    {
        $query->whereHas('accounts');
    }
}
