<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\IndexReportsRequest;
use App\Models\Category;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Support\Exports\PdfExport;
use App\Support\Reports\ReportBuilder;
use App\Support\Transactions\TransactionProjector;
use Carbon\CarbonImmutable;
use Symfony\Component\HttpFoundation\Response;

class ExportReportsController extends Controller
{
    /**
     * The report on screen, as a PDF.
     *
     * It reads the same request as the page and resolves the period and currency
     * through the same methods, so what downloads is what was being looked at.
     */
    public function __invoke(IndexReportsRequest $request, ReportBuilder $builder, PdfExport $pdf): Response
    {
        $tenant = $request->user()?->tenant;

        abort_unless($tenant instanceof Tenant, 403);

        $currencies = $tenant->activeCurrencies()->orderBy('code')->get();

        // Nothing has been recorded yet, so there is no report to hand over.
        abort_if($currencies->isEmpty(), 404);

        $currency = $request->currency($currencies);
        [$from, $to] = $request->period();

        $transactions = Transaction::query()
            ->where('currency_id', $currency->id)
            ->where('effective_date', '<=', $to->endOfMonth()->toDateString())
            ->with(TransactionProjector::RELATIONS)
            ->get();

        $period = sprintf('%s — %s', $from->isoFormat('MMM/YYYY'), $to->isoFormat('MMM/YYYY'));

        return $pdf->download(
            'pdf.report',
            [
                'title' => __('reports.pdf.title'),
                'subtitle' => sprintf('%s · %s', $period, $currency->code),
                'generatedAt' => __('reports.pdf.generated_at', [
                    'datetime' => CarbonImmutable::now()->isoFormat('D/MM/YYYY HH:mm'),
                ]),
                'symbol' => $currency->symbol,
                'report' => $builder->build(
                    $transactions,
                    Category::query()->get()->keyBy('id'),
                    $from,
                    $to,
                ),
            ],
            sprintf('%s %s %s', __('reports.pdf.title'), $from->format('Y-m'), $to->format('Y-m')),
        );
    }
}
