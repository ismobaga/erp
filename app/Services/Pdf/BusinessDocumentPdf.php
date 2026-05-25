<?php

namespace App\Services\Pdf;

use App\Models\Invoice;
use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;

class BusinessDocumentPdf
{
    public function make(string $view, array $data): DomPdf
    {
        return Pdf::loadView($view, $data)
            ->setOption([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'isFontSubsettingEnabled' => (bool) config('erp.pdf.font_subsetting', false),
                'dpi' => (int) config('erp.pdf.dpi', 120),
                'defaultFont' => (string) config('erp.pdf.default_font', 'DejaVu Sans'),
            ])
            ->setPaper(
                (string) config('erp.pdf.paper', 'a4'),
                (string) config('erp.pdf.orientation', 'portrait')
            );
    }

    public function shouldUseCompactForInvoice(Invoice $invoice): bool
    {
        return $this->shouldUseCompact(
            $invoice->items->count(),
            (string) $invoice->notes
        );
    }

    public function shouldUseCompactForQuote(Quote $quote): bool
    {
        return $this->shouldUseCompact(
            $quote->items->count(),
            (string) $quote->notes
        );
    }

    private function shouldUseCompact(int $itemsCount, string $notes): bool
    {
        $maxItems = (int) config('erp.pdf.compact_max_items', 6);
        $maxNotesLength = (int) config('erp.pdf.compact_max_notes_length', 500);

        return (bool) config('erp.pdf.compact_when_possible', true)
            && $itemsCount <= $maxItems
            && mb_strlen($notes) < $maxNotesLength;
    }
}
