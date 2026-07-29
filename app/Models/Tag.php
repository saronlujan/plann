<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\Tenancy\TenantContext;
use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string $color A palette name, or a hand-picked `#rrggbb`.
 */
class Tag extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<TagFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'color',
    ];

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
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
        $relation = $this->belongsToMany(Transaction::class);
        $tenantId = $this->tenant_id ?? app(TenantContext::class)->tenantId();

        return $tenantId === null ? $relation : $relation->withPivotValue('tenant_id', $tenantId);
    }
}
