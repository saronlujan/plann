<?php

namespace App\Enums;

enum PlanSlug: string
{
    case Basic = 'basic';

    case Pro = 'pro';

    public function label(): string
    {
        return match ($this) {
            self::Basic => 'Basic',
            self::Pro => 'Pro',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Basic => 'Para uso individual: 1 usuário.',
            self::Pro => 'Para times pequenos: até 5 usuários.',
        };
    }

    /**
     * Maximum number of users allowed on a tenant subscribed to this plan.
     */
    public function maxUsers(): int
    {
        return match ($this) {
            self::Basic => 1,
            self::Pro => 5,
        };
    }

    /**
     * Advertised monthly price in cents (billed annually).
     */
    public function monthlyPriceCents(): int
    {
        return match ($this) {
            self::Basic => 990,
            self::Pro => 1990,
        };
    }

    public function sortOrder(): int
    {
        return match ($this) {
            self::Basic => 1,
            self::Pro => 2,
        };
    }
}
