<?php

namespace App\Services\Pdf;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF;

class BusinessDocumentPdf
{
    public function make(string $view, array $data): PDF
    {
        return PDF::loadView($view, $data)
            ->setOption([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'isFontSubsettingEnabled' => (bool) config('erp.pdf.font_subsetting', true),
                'dpi' => (int) config('erp.pdf.dpi', 120),
                'defaultFont' => (string) config('erp.pdf.default_font', 'DejaVu Sans'),
            ])
            ->setPaper(
                (string) config('erp.pdf.paper', 'a4'),
                (string) config('erp.pdf.orientation', 'portrait')
            );
    }
}
