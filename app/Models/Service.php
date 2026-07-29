<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\Tenancy\TenantContext;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

/**
 * Something the workspace sells or delivers, priced on its own so that a single
 * contract can be broken down into what each part of it earned.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string|null $default_price
 * @property int|null $currency_id
 * @property string $color A palette name, or a hand-picked `#rrggbb`.
 */
class Service extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'default_price',
        'currency_id',
        'color',
    ];

    protected $casts = [
        'default_price' => 'decimal:2',
    ];

    /**
     * The composite foreign key is NO ACTION, so the link has to be cleared here.
     *
     * The line itself survives with its amount intact: retiring a service must
     * never change what a transaction was worth, only where it is counted. What
     * is left behind reads as unattributed in the reports.
     */
    protected static function booted(): void
    {
        static::deleting(function (Service $model): void {
            DB::table('service_transaction')
                ->where('tenant_id', $model->tenant_id)
                ->where('service_id', $model->id)
                ->update(['service_id' => null]);
        });
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * The currency the default price is quoted in.
     *
     * @return BelongsTo<Currency, $this>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * @return BelongsToMany<Transaction, $this>
     */
    public function transactions(): BelongsToMany
    {
        // Eager loading builds the relation on a bare instance, so tenant_id may
        // not be populated yet; the request's tenant context is the fallback.
        // withPivotValue both fills tenant_id on attach/sync and constrains reads,
        // so the pivot can never bridge two workspaces.
        $relation = $this->belongsToMany(Transaction::class)->withPivot('amount');
        $tenantId = $this->tenant_id ?? app(TenantContext::class)->tenantId();

        return $tenantId === null ? $relation : $relation->withPivotValue('tenant_id', $tenantId);
    }
}
