<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ app()->getLocale() === 'fr' ? 'Facture' : 'Invoice' }} {{ $invoice->invoice_number }}</title>
    <style>
        @include('portal.partials.styles')
        .bank-box {
            background: #f8faff;
            border-radius: 10px;
            padding: 16px;
            margin-top: 16px;
        }
        .bank-box p {
            font-size: 13px;
            color: #374151;
            line-height: 1.8;
        }
        .payments-list { list-style: none; }
        .payments-list li {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f4f9;
            font-size: 14px;
        }
        .payments-list li:last-child { border-bottom: none; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items th {
            padding: 10px 12px;
            text-align: left;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #57657a;
            background: #f8faff;
        }
        table.items td {
            padding: 12px;
            font-size: 14px;
            border-top: 1px solid #f0f4f9;
        }
        table.items tfoot td { font-weight: 700; padding-top: 14px; }
        .total-row td { font-size: 16px; font-weight: 900; color: #002045; }
        .balance-due { color: #ba1a1a !important; }
        .balance-zero { color: #166534 !important; }
    </style>
</head>

<body>
    @php
        $companyDisplayName = $company?->name ?: $company?->company_name ?: config('app.name');
        $statusLabel = __('erp.resources.invoice.statuses.' . $invoice->status) ?: $invoice->status;
    @endphp

    @include('portal.partials.nav', ['logoDataUri' => $logoDataUri ?? null])

    <div style="background:#fff;border-bottom:1px solid #e5eaf2;padding:12px 24px;display:flex;align-items:center;justify-content:space-between;">
        <a href="{{ route('portal.index', ['token' => $token]) }}" class="btn btn-outline btn-sm">← {{ __('erp.portal.ui.back') }}</a>
        <a href="{{ route('portal.invoice.pdf', ['token' => $token, 'invoice' => $invoice]) }}" class="btn btn-sm">⬇ {{ __('erp.portal.ui.download_pdf') }}</a>
    </div>

    <div class="container">

        {{-- Identity --}}
        <div class="card">
            <div class="card-title">{{ __('erp.portal.invoices.details') }}</div>
            <div class="meta-grid">
                <div>
                    <div class="meta-label">{{ __('erp.portal.invoices.number') }}</div>
                    <div class="meta-value">{{ $invoice->invoice_number }}</div>
                </div>
                <div>
                    <div class="meta-label">{{ __('erp.portal.quotes.status') }}</div>
                    <div class="meta-value">
                        <span class="badge badge-{{ $invoice->status }}">{{ $statusLabel }}</span>
                    </div>
                </div>
                <div>
                    <div class="meta-label">{{ __('erp.portal.invoices.issue_date') }}</div>
                    <div class="meta-value">{{ $invoice->issue_date?->format('d/m/Y') ?? '—' }}</div>
                </div>
                <div>
                    <div class="meta-label">{{ __('erp.portal.invoices.due_date') }}</div>
                    <div class="meta-value">{{ $invoice->due_date?->format('d/m/Y') ?? '—' }}</div>
                </div>
                <div>
                    <div class="meta-label">{{ __('erp.portal.invoices.total_amount') }}</div>
                    <div class="meta-value" style="font-size:20px;">FCFA {{ number_format((float) $invoice->total, 0, '.', ' ') }}</div>
                </div>
                <div>
                    <div class="meta-label">{{ __('erp.portal.invoices.balance_due') }}</div>
                    <div class="meta-value {{ (float) $invoice->balance_due > 0 ? 'balance-due' : 'balance-zero' }}" style="font-size:20px;">
                        FCFA {{ number_format((float) $invoice->balance_due, 0, '.', ' ') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Line items --}}
        @if($invoice->items->isNotEmpty())
            <div class="card">
                <div class="card-title">{{ __('erp.portal.invoices.line_items') }}</div>
                <table class="items">
                    <thead>
                        <tr>
                            <th>{{ __('erp.common.description') }}</th>
                            <th style="text-align:right;">{{ __('erp.portal.invoices.qty') }}</th>
                            <th style="text-align:right;">{{ __('erp.portal.invoices.unit_price') }}</th>
                            <th style="text-align:right;">{{ __('erp.portal.invoices.line_total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->items as $item)
                            <tr>
                                <td>{{ $item->description }}</td>
                                <td style="text-align:right;">{{ number_format((float) $item->quantity, 2, ',', ' ') }}</td>
                                <td style="text-align:right;">FCFA {{ number_format((float) $item->unit_price, 0, '.', ' ') }}</td>
                                <td style="text-align:right;font-weight:700;">FCFA {{ number_format((float) $item->line_total, 0, '.', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align:right;">{{ __('erp.portal.invoices.subtotal') }}</td>
                            <td style="text-align:right;">FCFA {{ number_format((float) $invoice->subtotal, 0, '.', ' ') }}</td>
                        </tr>
                        @if((float) $invoice->tax_total > 0)
                            <tr>
                                <td colspan="3" style="text-align:right;">{{ __('erp.portal.invoices.taxes') }}</td>
                                <td style="text-align:right;">FCFA {{ number_format((float) $invoice->tax_total, 0, '.', ' ') }}</td>
                            </tr>
                        @endif
                        @if((float) $invoice->discount_total > 0)
                            <tr>
                                <td colspan="3" style="text-align:right;">{{ __('erp.portal.invoices.discount') }}</td>
                                <td style="text-align:right;color:#166534;">− FCFA {{ number_format((float) $invoice->discount_total, 0, '.', ' ') }}</td>
                            </tr>
                        @endif
                        <tr class="total-row">
                            <td colspan="3" style="text-align:right;">{{ __('erp.portal.invoices.total_due') }}</td>
                            <td style="text-align:right;">FCFA {{ number_format((float) $invoice->total, 0, '.', ' ') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif

        {{-- Payments --}}
        @if($invoice->payments->isNotEmpty())
            <div class="card">
                <div class="card-title">{{ __('erp.portal.invoices.payment_history') }}</div>
                <ul class="payments-list">
                    @foreach($invoice->payments as $payment)
                        <li>
                            <span>
                                {{ $payment->payment_date?->format('d/m/Y') ?? '—' }}
                                · {{ __('erp.payment_methods.' . $payment->payment_method) ?: ucfirst($payment->payment_method) }}
                                @if($payment->reference) · {{ __('erp.portal.invoices.ref') }} : {{ $payment->reference }} @endif
                            </span>
                            <span style="font-weight:700;color:#166534;">FCFA {{ number_format((float) $payment->amount, 0, '.', ' ') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Bank details --}}
        @if($bankDetails['bank_name'] || $bankDetails['account_number'])
            <div class="card">
                <div class="card-title">{{ __('erp.portal.invoices.payment_info') }}</div>
                <div class="bank-box">
                    <p>
                        @if($bankDetails['bank_name'])<strong>{{ __('erp.portal.invoices.bank') }} :</strong> {{ $bankDetails['bank_name'] }}<br>@endif
                        @if($bankDetails['account_name'])<strong>{{ __('erp.portal.invoices.account_name') }} :</strong> {{ $bankDetails['account_name'] }}<br>@endif
                        @if($bankDetails['account_number'])<strong>{{ __('erp.portal.invoices.account_number') }} :</strong> {{ $bankDetails['account_number'] }}<br>@endif
                        @if($bankDetails['swift_code'])<strong>SWIFT / BIC :</strong> {{ $bankDetails['swift_code'] }}@endif
                    </p>
                </div>
            </div>
        @endif

        {{-- Notes --}}
        @if($invoice->notes)
            <div class="card">
                <div class="card-title">{{ __('erp.portal.invoices.notes') }}</div>
                <p style="font-size:14px;color:#374151;line-height:1.7;white-space:pre-line;">{{ $invoice->notes }}</p>
            </div>
        @endif

    </div>

    <footer>
        <p>{{ $companyDisplayName }} · {{ __('erp.portal.secure_portal') }} · {{ __('erp.portal.do_not_share') }}</p>
    </footer>
</body>

</html>