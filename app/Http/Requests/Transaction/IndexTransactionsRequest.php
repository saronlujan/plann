<?php

namespace App\Http\Requests\Transaction;

use App\Enums\TransactionMovementType;
use App\Enums\TransactionPeriodScale;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class IndexTransactionsRequest extends FormRequest
{
    /** @var array<int, string> */
    public const ORDERS = [
        'date_desc',
        'date_asc',
        'name_asc',
        'name_desc',
        'amount_desc',
        'amount_asc',
    ];

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Only the one bound worth enforcing.
     *
     * Every other filter is read through an accessor below that falls back to a
     * sane default, and that is deliberate: this screen is reached from a
     * bookmark, a back button and a stale link as often as from its own
     * controls. Rejecting `scale=fortnight` with an error page would punish
     * someone for a URL they never typed — showing them the current month is
     * both kinder and what they came for.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function scale(): TransactionPeriodScale
    {
        return TransactionPeriodScale::tryFrom($this->string('scale')->toString())
            ?? TransactionPeriodScale::Month;
    }

    /**
     * The day the visible stretch is measured from.
     */
    public function anchor(): CarbonImmutable
    {
        $date = $this->parse($this->string('date')->toString());

        if ($date !== null) {
            return $date;
        }

        // A month-only link is an anchor too: it lands on the first of it.
        $month = $this->string('period')->toString();

        return ($month === '' ? null : $this->parse($month.'-01'))
            ?? CarbonImmutable::now()->startOfDay();
    }

    /**
     * The stretch on screen.
     *
     * An explicit range wins over the scale, because someone who picked two dates
     * in the drawer asked for exactly those, not for something rounded to a week.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function range(): array
    {
        $from = $this->parse($this->string('from')->toString());
        $to = $this->parse($this->string('to')->toString());

        if ($from === null || $to === null) {
            return $this->scale()->bounds($this->anchor());
        }

        // Two dates the wrong way round are still two dates: read them as the
        // stretch between them rather than as an empty one.
        return $from->greaterThan($to)
            ? [$to->startOfDay(), $from->endOfDay()]
            : [$from->startOfDay(), $to->endOfDay()];
    }

    /**
     * True when the drawer's own dates decide the stretch, which is what tells
     * the period bar to stand down.
     */
    public function hasExplicitRange(): bool
    {
        return $this->parse($this->string('from')->toString()) !== null
            && $this->parse($this->string('to')->toString()) !== null;
    }

    public function search(): string
    {
        return trim($this->string('search')->toString());
    }

    public function movement(): ?TransactionMovementType
    {
        return TransactionMovementType::tryFrom($this->string('movement')->toString());
    }

    public function status(): ?string
    {
        $status = $this->string('status')->toString();

        return in_array($status, ['paid', 'pending'], true) ? $status : null;
    }

    /**
     * Which column the table is sorted on, and which way.
     *
     * The header cells are the only thing that sets this, so the list of names
     * here and the columns there have to stay in step.
     */
    public function order(): string
    {
        $order = $this->string('order')->toString();

        return in_array($order, self::ORDERS, true) ? $order : 'date_desc';
    }

    private function parse(string $value): ?CarbonImmutable
    {
        if ($value === '') {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
