<?php

namespace App\Support\Exports;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Turns a Blade view into a PDF the browser downloads.
 *
 * Everything that should look the same across every document — page size, the
 * header, the type — lives in resources/views/pdf/layout.blade.php, so a new
 * export is a view that extends it plus one call to here. Nothing about reports
 * is baked in: the next thing that needs a PDF reuses this as it stands.
 */
class PdfExport
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function download(string $view, array $data, string $filename): Response
    {
        return Pdf::loadView($view, $data)
            ->setPaper('a4')
            ->download($this->filename($filename));
    }

    /**
     * A name that survives the trip to any filesystem.
     *
     * Accents and spaces make it through most browsers and mangle on some, and a
     * report is something people keep — so it is folded to plain ASCII once here
     * rather than being a surprise later.
     */
    private function filename(string $filename): string
    {
        return Str::slug($filename).'.pdf';
    }
}
