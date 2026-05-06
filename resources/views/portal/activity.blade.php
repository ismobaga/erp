<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('erp.portal.activity.title') }} — {{ $client->company_name ?: $client->contact_name }}</title>
    <style>
        @include('portal.partials.styles')
    </style>
</head>
<body>

@include('portal.partials.nav')

<div class="container">

    @if($activityLogs->isEmpty())
        <div class="card">
            <div class="empty">
                <div class="empty-icon">📜</div>
                <p style="font-size:16px;font-weight:700;margin-bottom:8px;">{{ __('erp.portal.activity.empty') }}</p>
                <p>{{ __('erp.portal.activity.empty_hint') }}</p>
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-title">{{ __('erp.portal.activity.title') }}</div>
            <ul class="timeline">
                @foreach($activityLogs as $log)
                    @php
                        $label = match($log->action) {
                            'invoice_number_assigned'  => app()->getLocale() === 'fr' ? 'Facture créée' : 'Invoice created',
                            'portal_quote_approved'    => app()->getLocale() === 'fr' ? 'Devis approuvé' : 'Quote approved',
                            'portal_quote_rejected'    => app()->getLocale() === 'fr' ? 'Devis refusé' : 'Quote rejected',
                            'quote_converted_to_invoice' => app()->getLocale() === 'fr' ? 'Devis converti en facture' : 'Quote converted to invoice',
                            'payment_recorded'         => app()->getLocale() === 'fr' ? 'Paiement enregistré' : 'Payment recorded',
                            'payment_flagged'          => app()->getLocale() === 'fr' ? 'Paiement signalé' : 'Payment flagged',
                            default                    => str_replace('_', ' ', ucfirst($log->action)),
                        };
                        $meta = $log->meta_json ?? [];
                    @endphp
                    <li>
                        <div class="timeline-dot"></div>
                        <div class="timeline-date">{{ $log->created_at?->format('d/m/Y H:i') ?? '—' }}</div>
                        <div class="timeline-text">
                            <strong>{{ $label }}</strong>
                            @if(!empty($meta['invoice_number']))
                                &nbsp;· {{ $meta['invoice_number'] }}
                            @elseif(!empty($meta['quote_number']))
                                &nbsp;· {{ $meta['quote_number'] }}
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

</div>

<footer>
    <p>{{ $company?->company_name ?: config('app.name') }} · {{ __('erp.portal.secure_portal') }} · {{ __('erp.portal.do_not_share') }}</p>
</footer>
</body>
</html>
