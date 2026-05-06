<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $quote->quote_number }} — {{ $client->company_name ?: $client->contact_name }}</title>
    <style>
        @include('portal.partials.styles')
        table.items { width: 100%; border-collapse: collapse; }
        table.items th {
            padding: 10px 12px; text-align: left; font-size: 11px; font-weight: 800;
            text-transform: uppercase; letter-spacing: 0.1em; color: #57657a; background: #f8faff;
        }
        table.items td { padding: 12px; font-size: 14px; border-top: 1px solid #f0f4f9; }
        table.items tfoot td { font-weight: 700; padding-top: 14px; }
        .total-row td { font-size: 16px; font-weight: 900; color: #002045; }
    </style>
</head>
<body>

@include('portal.partials.nav')

<div style="background:#fff;border-bottom:1px solid #e5eaf2;padding:12px 24px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
    <a href="{{ route('portal.quotes', ['token' => $token]) }}" class="btn btn-outline btn-sm">← {{ __('erp.portal.quote_detail.back') }}</a>

    @if($quote->canBeAccepted())
        <form method="POST" action="{{ route('portal.quote.approve', ['token' => $token, 'quote' => $quote]) }}"
              onsubmit="return confirm('{{ __('erp.portal.quotes.confirm_approve') }}')">
            @csrf
            <button type="submit" class="btn btn-sm btn-success">✓ {{ __('erp.portal.quotes.approve') }}</button>
        </form>
        <form method="POST" action="{{ route('portal.quote.reject', ['token' => $token, 'quote' => $quote]) }}"
              onsubmit="return confirm('{{ __('erp.portal.quotes.confirm_reject') }}')">
            @csrf
            <button type="submit" class="btn btn-sm btn-danger">✕ {{ __('erp.portal.quotes.reject') }}</button>
        </form>
    @endif
</div>

<div class="container">

    @if(session('portal_success'))
        <div class="flash-success">✓ {{ session('portal_success') }}</div>
    @endif

    {{-- Quote details --}}
    <div class="card">
        <div class="card-title">{{ __('erp.portal.quote_detail.details') }}</div>
        <div class="meta-grid">
            <div>
                <div class="meta-label">{{ __('erp.portal.quote_detail.number') }}</div>
                <div class="meta-value">{{ $quote->quote_number }}</div>
            </div>
            <div>
                <div class="meta-label">{{ __('erp.portal.quotes.status') }}</div>
                <div class="meta-value">
                    <span class="badge badge-{{ $quote->status }}">{{ __('erp.portal.quotes.statuses.' . $quote->status) ?: $quote->status }}</span>
                </div>
            </div>
            <div>
                <div class="meta-label">{{ __('erp.portal.quotes.issue_date') }}</div>
                <div class="meta-value">{{ $quote->issue_date?->format('d/m/Y') ?? '—' }}</div>
            </div>
            <div>
                <div class="meta-label">{{ __('erp.portal.quotes.valid_until') }}</div>
                <div class="meta-value">{{ $quote->valid_until?->format('d/m/Y') ?? '—' }}</div>
            </div>
            <div>
                <div class="meta-label">{{ __('erp.portal.quotes.total') }}</div>
                <div class="meta-value" style="font-size:20px;">FCFA {{ number_format((float) $quote->total, 0, '.', ' ') }}</div>
            </div>
            @if($quote->invoice)
                <div>
                    <div class="meta-label">{{ __('erp.portal.quote_detail.linked_invoice') }}</div>
                    <div class="meta-value">
                        <a href="{{ route('portal.invoice', ['token' => $token, 'invoice' => $quote->invoice]) }}" style="color:#002045;">{{ $quote->invoice->invoice_number }}</a>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Line items --}}
    @if($quote->items->isNotEmpty())
        <div class="card">
            <div class="card-title">{{ __('erp.portal.quote_detail.line_items') }}</div>
            <table class="items">
                <thead>
                    <tr>
                        <th>{{ __('erp.common.description') }}</th>
                        <th style="text-align:right;">{{ __('erp.portal.quote_detail.qty') }}</th>
                        <th style="text-align:right;">{{ __('erp.portal.quote_detail.unit_price') }}</th>
                        <th style="text-align:right;">{{ __('erp.portal.quote_detail.line_total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($quote->items as $item)
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
                        <td colspan="3" style="text-align:right;">{{ __('erp.portal.quote_detail.subtotal') }}</td>
                        <td style="text-align:right;">FCFA {{ number_format((float) $quote->subtotal, 0, '.', ' ') }}</td>
                    </tr>
                    @if((float) $quote->tax_total > 0)
                        <tr>
                            <td colspan="3" style="text-align:right;">{{ __('erp.portal.quote_detail.taxes') }}</td>
                            <td style="text-align:right;">FCFA {{ number_format((float) $quote->tax_total, 0, '.', ' ') }}</td>
                        </tr>
                    @endif
                    @if((float) $quote->discount_total > 0)
                        <tr>
                            <td colspan="3" style="text-align:right;">{{ __('erp.portal.quote_detail.discount') }}</td>
                            <td style="text-align:right;color:#166534;">− FCFA {{ number_format((float) $quote->discount_total, 0, '.', ' ') }}</td>
                        </tr>
                    @endif
                    <tr class="total-row">
                        <td colspan="3" style="text-align:right;">{{ __('erp.portal.quote_detail.total') }}</td>
                        <td style="text-align:right;">FCFA {{ number_format((float) $quote->total, 0, '.', ' ') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif

    {{-- Notes --}}
    @if($quote->notes)
        <div class="card">
            <div class="card-title">{{ __('erp.portal.quote_detail.notes') }}</div>
            <p style="font-size:14px;color:#374151;line-height:1.7;white-space:pre-line;">{{ $quote->notes }}</p>
        </div>
    @endif

</div>

<footer>
    <p>{{ $company?->company_name ?: config('app.name') }} · {{ __('erp.portal.secure_portal') }} · {{ __('erp.portal.do_not_share') }}</p>
</footer>
</body>
</html>
