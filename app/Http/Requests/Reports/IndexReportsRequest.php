<?php

namespace App\Http\Requests\Reports;

use App\Models\Currency;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;

class IndexReportsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Every filter is optional: opening the page with no query string must show
     * a sensible default report rather than a validation error.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'string', 'date_format:Y-m'],
            'to' => ['nullable', 'string', 'date_format:Y-m'],
            'currency_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * Which currency the report is drawn in.
     *
     * Resolved here rather than in a controller because the screen and the PDF
     * both have to land on the same one — a download that quietly reported a
     * different period than the page it was started from would be worse than no
     * download at all.
     *
     * @param  Collection<int, Currency>  $currencies
     */
    public function currency(Collection $currencies): Currency
    {
        return $currencies->firstWhere('id', $this->integer('currency_id')) ?? $currencies->first();
    }

    /**
     * Defaults to the year to date, which is the range people actually want when
     * they open a report. The range is clamped so a wide span cannot be used to
     * project hundreds of months.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function period(): array
    {
        $now = CarbonImmutable::now()->startOfMonth();

        $to = $this->parseMonth($this->string('to')->toString()) ?? $now;
        $from = $this->parseMonth($this->string('from')->toString()) ?? $now->startOfYear();

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
