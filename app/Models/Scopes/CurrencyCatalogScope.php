<?php

namespace App\Models\Scopes;

use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Currencies are half shared, half private.
 *
 * Rows with a null tenant_id are the curated catalogue every workspace sees; a
 * row carrying a tenant_id belongs to that workspace alone. Unlike TenantScope
 * this must never fall back to "no rows" when there is no tenant context —
 * seeders, the console and guest requests still need the shared catalogue.
 *
 * @implements Scope<Model>
 */
class CurrencyCatalogScope implements Scope
{
    /**
     * @param  Builder<covariant Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = app(TenantContext::class)->tenantId();
        $column = $model->qualifyColumn('tenant_id');

        $builder->where(function (Builder $query) use ($column, $tenantId): void {
            $query->whereNull($column);

            if ($tenantId !== null) {
                $query->orWhere($column, $tenantId);
            }
        });
    }
}
