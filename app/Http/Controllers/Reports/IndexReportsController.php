<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\IndexReportsRequest;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Support\Reports\ReportBuilder;
use Inertia\Inertia;
use Inertia\Response;

class IndexReportsController extends Controller
{
    public function __invoke(IndexReportsRequest $request, ReportBuilder $builder): Response
    {
        $tenant = $request->user()?->tenant;

        abort_unless($tenant instanceof Tenant, 403);

        $currencies = $tenant->activeCurrencies()->orderBy('code')->get();

        if ($currencies->isEmpty()) {
            return Inertia::render('Reports/Index', ['ready' => false]);
        }

        $currency = $request->currency($currencies);
        [$from, $to] = $request->period();

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
}
