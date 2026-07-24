<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use LogicException;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model): void {
            $tenantId = app(TenantContext::class)->tenantId();

            if ($tenantId === null) {
                return;
            }

            $currentTenantId = $model->getAttribute('tenant_id');

            if ($currentTenantId === null) {
                $model->setAttribute('tenant_id', $tenantId);

                return;
            }

            if ((int) $currentTenantId !== $tenantId) {
                throw new LogicException('Tenant mismatch while creating a record.');
            }
        });

        static::saving(function (Model $model): void {
            $tenantId = app(TenantContext::class)->tenantId();

            if ($tenantId === null || ! $model->isDirty('tenant_id')) {
                return;
            }

            if ((int) $model->getAttribute('tenant_id') !== $tenantId) {
                throw new LogicException('Tenant mismatch while saving a record.');
            }
        });
    }
}
