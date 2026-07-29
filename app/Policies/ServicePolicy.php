<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    public function update(User $user, Service $service): bool
    {
        return $user->tenant_id === $service->tenant_id;
    }

    public function delete(User $user, Service $service): bool
    {
        return $user->tenant_id === $service->tenant_id;
    }
}
