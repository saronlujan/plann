<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\IndexReportsRequest;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Support\Reports\ReportBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class IndexReportsController extends Controller
{
    public function __invoke(IndexReportsRequest $request, ReportBuilder $builder): Response
    {
        $tenant = $request->user()?->tenant;

        abort_unless($tenant instanceof Tenant, 403);

        // A currency with no account has nothing to report on.
        $currencies = $tenant->activeCurrencies()->usable()->orderBy('code')->get();

        if ($currencies->isEmpty()) {
            return Inertia::render('Reports/Index', ['ready' => false]);
        }

        $currency = $this->resolveCurrency($currencies, $request->integer('currency_id'));
        [$from, $to] = $this->resolvePeriod($request);

        $transactions = Transaction::query()
            ->where('currency_id', $currency->id)
            ->where('effective_date', '<=', $to->endOfMonth()->toDateString())
            ->with(['currency', 'account'])
            ->get();

        return Inertia::render('Reports/Index', [
            'ready' => true,
            'filters' => [
                'from' => $from->format('Y-m'),
                'to' => $to->format('Y-m'),
                'currency_id' => $currency->id,
            ],
            'currency' => [
                'id' => $currency->id,
                'code' => $currency->code,
                'symbol' => $currency->symbol,
            ],
            'currencyOptions' => $currencies
                ->map(fn (Currency $option): array => [
                    'value' => (string) $option->id,
                    'label' => $option->code.' - '.$option->name,
                ])
                ->all(),
            'report' => $builder->build(
                $transactions,
                Category::query()->get()->keyBy('id'),
                $from,
                $to,
            ),
        ]);
    }

    /**
     * @param  Collection<int, Currency>  $currencies
     */
    private function resolveCurrency(Collection $currencies, int $requested): Currency
    {
        return $currencies->firstWhere('id', $requested) ?? $currencies->first();
    }

    /**
     * Defaults to the year to date, which is the range people actually want when
     * they open a report. The range is clamped so a wide span cannot be used to
     * project hundreds of months.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function resolvePeriod(IndexReportsRequest $request): array
    {
        $now = CarbonImmutable::now()->startOfMonth();

        $to = $this->parseMonth($request->string('to')->toString()) ?? $now;
        $from = $this->parseMonth($request->string('from')->toString()) ?? $now->startOfYear();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        if ($from->diffInMonths($to) > 35) {
            $from = $to->subMonths(35);
        }

        return [$from, $to];
    }

    private function parseMonth(string $month): ?CarbonImmutable
    {
        if ($month === '') {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();
        } catch (\Throwable) {
            return null;
        }
    }
}
