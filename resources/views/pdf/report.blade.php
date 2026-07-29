@extends('pdf.layout')

@php
    /** Money as the locale writes it, with the currency symbol in front. */
    $money = fn (string $value): string => $symbol.' '.number_format((float) $value, 2, ',', '.');
    $signed = fn (string $value): string => (float) $value < 0 ? 'negative' : 'positive';
@endphp

@section('content')
    <h2>{{ __('reports.pdf.summary') }}</h2>
    <table>
        <thead>
            <tr>
                <th>{{ __('reports.pdf.figure') }}</th>
                <th class="num">{{ __('reports.pdf.expected') }}</th>
                <th class="num">{{ __('reports.pdf.realized') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ __('reports.pdf.income') }}</td>
                <td class="num">{{ $money($report['summary']['income']) }}</td>
                <td class="num">{{ $money($report['summary']['income_paid']) }}</td>
            </tr>
            <tr>
                <td>{{ __('reports.pdf.expenses') }}</td>
                <td class="num">{{ $money($report['summary']['expenses']) }}</td>
                <td class="num">{{ $money($report['summary']['expenses_paid']) }}</td>
            </tr>
            <tr>
                <td><strong>{{ __('reports.pdf.net') }}</strong></td>
                <td class="num {{ $signed($report['summary']['net']) }}">
                    <strong>{{ $money($report['summary']['net']) }}</strong>
                </td>
                <td class="num {{ $signed($report['summary']['net_paid']) }}">
                    <strong>{{ $money($report['summary']['net_paid']) }}</strong>
                </td>
            </tr>
        </tbody>
    </table>

    <h2>{{ __('reports.pdf.monthly') }}</h2>
    <table>
        <thead>
            <tr>
                <th>{{ __('reports.pdf.month') }}</th>
                <th class="num">{{ __('reports.pdf.income') }}</th>
                <th class="num">{{ __('reports.pdf.expenses') }}</th>
                <th class="num">{{ __('reports.pdf.net') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($report['monthly'] as $month)
                <tr>
                    <td>{{ $month['label'] }}</td>
                    <td class="num">{{ $money($month['income']) }}</td>
                    <td class="num">{{ $money($month['expenses']) }}</td>
                    <td class="num {{ $signed($month['net']) }}">{{ $money($month['net']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>{{ __('reports.pdf.by_category') }}</h2>
    @if (count($report['by_category']) === 0)
        <p class="muted">{{ __('reports.pdf.empty') }}</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>{{ __('reports.pdf.category') }}</th>
                    <th>{{ __('reports.pdf.kind') }}</th>
                    <th class="num">{{ __('reports.pdf.total') }}</th>
                    <th class="num">{{ __('reports.pdf.share') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['by_category'] as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td class="muted">{{ __('enums.category_type.'.$row['type']) }}</td>
                        <td class="num">{{ $money($row['total']) }}</td>
                        <td class="num muted">{{ number_format((float) $row['share'], 1, ',', '.') }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>{{ __('reports.pdf.by_account') }}</h2>
    @if (count($report['by_account']) === 0)
        <p class="muted">{{ __('reports.pdf.empty') }}</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>{{ __('reports.pdf.account') }}</th>
                    <th class="num">{{ __('reports.pdf.income') }}</th>
                    <th class="num">{{ __('reports.pdf.expenses') }}</th>
                    <th class="num">{{ __('reports.pdf.net') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['by_account'] as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td class="num">{{ $money($row['income']) }}</td>
                        <td class="num">{{ $money($row['expenses']) }}</td>
                        <td class="num {{ $signed($row['net']) }}">{{ $money($row['net']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
