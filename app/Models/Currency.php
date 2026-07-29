<?php

namespace App\Models;

use App\Models\Scopes\CurrencyCatalogScope;
use Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
     * @return HasMany<Account, $this>
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }
}
