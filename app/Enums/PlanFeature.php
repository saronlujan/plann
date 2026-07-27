<?php

namespace App\Enums;

/**
 * Capabilities a plan can unlock.
 *
 * The app is single-user by design, so plans are separated by what the product
 * can do, never by how many people use it.
 *
 * Which tier grants each one lives in PlanSlug::features().
 */
enum PlanFeature: string
{
    /**
     * Keeping more than one currency active. Serves the freelancer billing
     * abroad, the crypto holder and the border resident.
     */
    case MultiCurrency = 'multi_currency';

    public function label(): string
    {
        return __('enums.plan_feature.'.$this->value);
    }
}
