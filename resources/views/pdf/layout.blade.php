{{--
    Shared skin for every PDF the app produces. A new export extends this and
    fills in @section('content'); nothing below is specific to any one document.

    DejaVu Sans is the one font DomPDF ships that carries the accents this app
    writes in — the default would drop the ç and the ã silently.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 28px 32px 44px; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #18181b;
            margin: 0;
        }

        .masthead {
            border-bottom: 1px solid #e4e4e7;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }

        .brand {
            font-size: 9px;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #a1a1aa;
        }

        h1 {
            font-size: 15px;
            margin: 4px 0 0;
        }

        .subtitle {
            color: #71717a;
            margin-top: 3px;
        }

        h2 {
            font-size: 11px;
            margin: 18px 0 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #71717a;
            border-bottom: 1px solid #e4e4e7;
            padding: 5px 6px;
        }

        td {
            border-bottom: 1px solid #f4f4f5;
            padding: 6px;
        }

        /* Money lines up only when the digits do. */
        .num { text-align: right; }
        .positive { color: #047857; }
        .negative { color: #b91c1c; }
        .muted { color: #71717a; }

        /* Repeated on every page by DomPDF's fixed positioning. */
        .footer {
            position: fixed;
            bottom: -28px;
            left: 0;
            right: 0;
            font-size: 8px;
            color: #a1a1aa;
            border-top: 1px solid #f4f4f5;
            padding-top: 5px;
        }

        .footer .right { float: right; }
    </style>
</head>
<body>
    <div class="masthead">
        <div class="brand">{{ config('app.name') }}</div>
        <h1>{{ $title }}</h1>
        @isset($subtitle)
            <div class="subtitle">{{ $subtitle }}</div>
        @endisset
    </div>

    @yield('content')

    <div class="footer">
        {{ $generatedAt }}
        <span class="right">{{ config('app.name') }}</span>
    </div>
</body>
</html>
