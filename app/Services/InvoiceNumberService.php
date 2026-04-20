<?php

namespace App\Services;

use App\Models\Invoice;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class InvoiceNumberService
{
    public function generate(CarbonInterface|string|null $issueDate = null): string
    {
        $date = $issueDate instanceof CarbonInterface
            ? Carbon::instance($issueDate)
            : Carbon::parse($issueDate ?? now());

        $prefix = trim((string) config('erp.billing.invoice_numbering.prefix', 'INV'));
        $padding = max(3, (int) config('erp.billing.invoice_numbering.padding', 4));
        $separator = (string) config('erp.billing.invoice_numbering.separator', '-');
        $reset = (string) config('erp.billing.invoice_numbering.reset', 'yearly');

        $segments = [$prefix];

        if ($reset === 'monthly') {
            $segments[] = $date->format('Y');
            $segments[] = $date->format('m');
        } elseif ($reset === 'yearly') {
            $segments[] = $date->format('Y');
        }

        $base = collect($segments)
            ->filter(fn(?string $segment): bool => filled($segment))
            ->implode($separator);

        $query = Invoice::query();

        if ($reset === 'monthly') {
            $query->whereBetween('issue_date', [
                $date->copy()->startOfMonth()->toDateString(),
                $date->copy()->endOfMonth()->toDateString(),
            ]);
        } elseif ($reset === 'yearly') {
            $query->whereBetween('issue_date', [
                $date->copy()->startOfYear()->toDateString(),
                $date->copy()->endOfYear()->toDateString(),
            ]);
        }

        $baseWithSeparator = $base . $separator;

        $highestSequence = $query
            ->pluck('invoice_number')
            ->reduce(function (int $carry, ?string $invoiceNumber) use ($baseWithSeparator): int {
                if (!filled($invoiceNumber) || !str_starts_with($invoiceNumber, $baseWithSeparator)) {
                    return $carry;
                }

                $suffix = substr($invoiceNumber, strlen($baseWithSeparator));

                if (!ctype_digit($suffix)) {
                    return $carry;
                }

                return max($carry, (int) $suffix);
            }, 0);

        return $baseWithSeparator . str_pad((string) ($highestSequence + 1), $padding, '0', STR_PAD_LEFT);
    }
}
