<?php

namespace App\Enums;

use Carbon\CarbonImmutable;

/**
 * How wide a stretch the transaction list shows at once, and therefore how far
 * the arrows beside it step.
 */
enum TransactionPeriodScale: string
{
    case Day = 'day';

    case Week = 'week';

    case Month = 'month';

    public function label(): string
    {
        return __('enums.transaction_period_scale.'.$this->value);
    }

    /**
     * The stretch containing the given day.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function bounds(CarbonImmutable $anchor): array
    {
        return match ($this) {
            self::Day => [$anchor->startOfDay(), $anchor->endOfDay()],
            self::Week => [$anchor->startOfWeek(), $anchor->endOfWeek()],
            self::Month => [$anchor->startOfMonth(), $anchor->endOfMonth()],
        };
    }

    /**
     * The same stretch, moved by `$steps` of its own size. What the arrows do.
     */
    public function shift(CarbonImmutable $anchor, int $steps): CarbonImmutable
    {
        return match ($this) {
            self::Day => $anchor->addDays($steps),
            self::Week => $anchor->addWeeks($steps),
            // No overflow: stepping back from the 31st lands on the 30th rather
            // than skipping into the month after.
            self::Month => $anchor->addMonthsNoOverflow($steps),
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $scale): array => ['value' => $scale->value, 'label' => $scale->label()],
            self::cases(),
        );
    }
}
