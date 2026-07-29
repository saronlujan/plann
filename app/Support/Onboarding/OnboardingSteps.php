<?php

namespace App\Support\Onboarding;

use App\Models\Account;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\Transaction;

/**
 * Where a workspace stands in its first run.
 *
 * Derived from what exists rather than stored: a flag would have to be kept in
 * step with the very records it describes, and would go stale the moment
 * somebody deleted one.
 */
class OnboardingSteps
{
    public const STEPS = ['account', 'category', 'tag', 'contact', 'transaction'];

    /**
     * @return array<string, bool>
     */
    public function completed(): array
    {
        return [
            'account' => Account::query()->exists(),
            'category' => Category::query()->exists(),
            'tag' => Tag::query()->exists(),
            'contact' => Contact::query()->exists(),
            'transaction' => Transaction::query()->exists(),
        ];
    }

    /**
     * The step to show: the first unfinished one, unless the visitor asked for
     * another — which is how skipping the optional ones works.
     *
     * @param  array<string, bool>  $completed
     */
    public function current(array $completed, string $requested = ''): string
    {
        if (in_array($requested, self::STEPS, true)) {
            return $requested;
        }

        foreach (self::STEPS as $step) {
            if ($completed[$step] === false) {
                return $step;
            }
        }

        return 'transaction';
    }

    /**
     * Whether the workspace still needs the guided run.
     *
     * An account is the one thing nothing else works without, so it alone
     * decides — otherwise somebody who deliberately skipped tags would be sent
     * back here on every visit.
     */
    public function isPending(): bool
    {
        return ! Account::query()->exists();
    }
}
