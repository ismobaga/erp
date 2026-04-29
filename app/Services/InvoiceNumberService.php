<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class InvoiceNumberService
{
    public function __construct(
        private readonly SequenceService $sequences,
    ) {}

    public function generate(CarbonInterface|string|null $issueDate = null): string
    {
        $date = $issueDate instanceof CarbonInterface
            ? Carbon::instance($issueDate)
            : Carbon::parse($issueDate ?? now());

        $prefix    = trim((string) config('erp.billing.invoice_numbering.prefix', 'INV'));
        $padding   = max(3, (int) config('erp.billing.invoice_numbering.padding', 4));
        $separator = (string) config('erp.billing.invoice_numbering.separator', '-');
        $reset     = (string) config('erp.billing.invoice_numbering.reset', 'yearly');

        $segments = [$prefix];

        if ($reset === 'monthly') {
            $segments[] = $date->format('Y');
            $segments[] = $date->format('m');
            $period = $date->format('Y-m');
        } elseif ($reset === 'yearly') {
            $segments[] = $date->format('Y');
            $period = $date->format('Y');
        } else {
            $period = 'all';
        }

        $base = collect($segments)
            ->filter(fn(?string $segment): bool => filled($segment))
            ->implode($separator);

        $seq = $this->sequences->next('invoice', $period);

        return $base . $separator . str_pad((string) $seq, $padding, '0', STR_PAD_LEFT);
    }
}
