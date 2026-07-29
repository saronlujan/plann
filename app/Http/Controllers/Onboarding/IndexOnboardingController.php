<?php

namespace App\Http\Controllers\Onboarding;

use App\Enums\AccountKind;
use App\Enums\CategoryType;
use App\Enums\ContactType;
use App\Enums\LabelColor;
use App\Enums\PlanFeature;
use App\Enums\TransactionInstallmentFrequency;
use App\Enums\TransactionMovementType;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Currency;
use App\Models\Tag;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Support\Onboarding\OnboardingSteps;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexOnboardingController extends Controller
{
    /**
     * The guided first run: account, category, tag, contact, first entry.
     *
     * Everything is posted to the modules' own endpoints, so nothing here has a
     * second set of validation rules to keep in step.
     */
    public function __invoke(Request $request, OnboardingSteps $steps): Response
    {
        $tenant = $request->user()?->tenant()->firstOrFail();
        $done = $steps->completed();
        $currencyOptions = $this->currencyOptions($tenant);

        return Inertia::render('Onboarding/Index', [
            'completed' => $done,
            // The opening blurb is written for the plan: one speaks to somebody
            // organising their own money, the other to somebody running a business.
            'plan' => $tenant->plan_slug->value,
            'userName' => $request->user()?->name,
            'currentStep' => $steps->current($done, $request->string('step')->toString()),
            'currencyOptions' => $currencyOptions,
            // The currency chosen at signup, not whichever sorts first: a
            // Brazilian workspace on Pro sees the whole catalogue, and that list
            // starts at ARS.
            'defaultCurrencyId' => $this->defaultCurrencyId($tenant, $currencyOptions),
            'accountKindOptions' => AccountKind::options(),
            'categoryTypeOptions' => array_map(
                fn (CategoryType $type): array => ['value' => $type->value, 'label' => $type->label()],
                CategoryType::cases(),
            ),
            'movementTypeOptions' => array_map(
                fn (TransactionMovementType $type): array => ['value' => $type->value, 'label' => $type->label()],
                // A transfer needs two accounts; the first entry never is one.
                array_filter(
                    TransactionMovementType::cases(),
                    fn (TransactionMovementType $type): bool => $type !== TransactionMovementType::Transfer,
                ),
            ),
            'scheduleTypeOptions' => array_map(
                fn (TransactionType $type): array => ['value' => $type->value, 'label' => $type->label()],
                TransactionType::cases(),
            ),
            'frequencyOptions' => array_map(
                fn (TransactionInstallmentFrequency $frequency): array => ['value' => $frequency->value, 'label' => $frequency->label()],
                TransactionInstallmentFrequency::cases(),
            ),
            'colorOptions' => LabelColor::options(),
            'contactTypeOptions' => array_map(
                fn (ContactType $type): array => ['value' => $type->value, 'label' => $type->label()],
                ContactType::cases(),
            ),
            'accountOptions' => Account::query()
                ->orderBy('name')
                ->get(['id', 'name', 'currency_id'])
                ->map(fn (Account $account): array => [
                    'value' => (string) $account->id,
                    'label' => $account->name,
                    'currency_id' => $account->currency_id,
                ])
                ->all(),
            'categoryOptions' => Category::query()
                ->orderBy('name')
                ->get(['id', 'name', 'type'])
                ->map(fn (Category $category): array => [
                    'value' => (string) $category->id,
                    'label' => $category->name,
                    'type' => $category->type->value,
                ])
                ->all(),
            'tagOptions' => Tag::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Tag $tag): array => ['value' => $tag->id, 'label' => $tag->name])
                ->all(),
            // What each step produced, so coming back to a finished one opens on
            // the record instead of a form that would only duplicate it.
            'created' => [
                'account' => Account::query()->latest('id')->value('name'),
                'category' => Category::query()->latest('id')->value('name'),
                'tag' => Tag::query()->latest('id')->value('name'),
                'contact' => Contact::query()->latest('id')->value('name'),
                'transaction' => Transaction::query()->latest('id')->value('description'),
            ],
        ]);
    }

    /**
     * Which currency a form should open on.
     *
     * @param  array<int, array{value: string, label: string, code: string, symbol: string}>  $options
     */
    private function defaultCurrencyId(Tenant $tenant, array $options): string
    {
        $signup = (string) $tenant->currency_id;

        foreach ($options as $option) {
            if ($option['value'] === $signup) {
                return $signup;
            }
        }

        return $options[0]['value'] ?? '';
    }

    /**
     * The same rule the accounts page follows: a single-currency plan is held to
     * what it signed up with, Pro gets the catalogue.
     *
     * @return array<int, array{value: string, label: string, code: string, symbol: string}>
     */
    private function currencyOptions(Tenant $tenant): array
    {
        $query = Currency::query();

        if (! $tenant->hasFeature(PlanFeature::MultiCurrency)) {
            $inUse = $tenant->activeCurrencies()->pluck('currencies.id')->all();

            $query->whereIn('id', $inUse === [] ? array_filter([$tenant->currency_id]) : $inUse);
        }

        return $query
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'symbol'])
            ->map(fn (Currency $currency): array => [
                'value' => (string) $currency->id,
                'label' => $currency->code.' - '.$currency->name,
                'code' => $currency->code,
                'symbol' => $currency->symbol,
            ])
            ->all();
    }
}
