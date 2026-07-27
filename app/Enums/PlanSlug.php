<?php

namespace App\Enums;

enum PlanSlug: string
{
    case Basic = 'basic';

    case Pro = 'pro';

    public function label(): string
    {
        return __('enums.plan_slug.'.$this->value);
    }

    public function description(): string
    {
        return __('enums.plan_slug_description.'.$this->value);
    }

    /**
     * Capabilities unlocked by this plan.
     *
     * This is the single place a feature moves between tiers.
     *
     * @return array<int, PlanFeature>
     */
    public function features(): array
    {
        return match ($this) {
            self::Basic => [],
            self::Pro => PlanFeature::cases(),
        };
    }

    public function hasFeature(PlanFeature $feature): bool
    {
        return in_array($feature, $this->features(), true);
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
