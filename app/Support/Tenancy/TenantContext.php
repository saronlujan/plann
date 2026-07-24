<?php

namespace App\Support\Tenancy;

class TenantContext
{
    private ?int $tenantId = null;

    public function setTenantId(?int $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    public function tenantId(): ?int
    {
        return $this->tenantId;
    }

    public function clear(): void
    {
        $this->tenantId = null;
    }

    public function hasTenant(): bool
    {
        return $this->tenantId !== null;
    }
}
