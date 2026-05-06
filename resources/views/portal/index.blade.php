<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $client->company_name ?: $client->contact_name }} — {{ __('erp.portal.secure_portal') }}</title>
    <style>
        @include('portal.partials.styles')
    </style>
</head>
<body>

@include('portal.partials.nav')

<div class="container">

    @if(session('portal_success'))
        <div class="flash-success">✓ {{ session('portal_success') }}</div>
    @endif

    <div class="card">
        <h1 style="font-size:22px;font-weight:900;color:#002045;margin-bottom:4px;">{{ $client->company_name ?: $client->contact_name }}</h1>
        <p style="font-size:14px;color:#57657a;">{{ __('erp.portal.welcome_hint') }}</p>
    </div>

    @if($invoices->isEmpty())
        <div class="card">
            <div class="empty">
                <div class="empty-icon">📄</div>
                <p style="font-size:16px;font-weight:700;margin-bottom:8px;">{{ __('erp.portal.nav.invoices') }}</p>
                <p>{{ app()->getLocale() === 'fr' ? 'Vos factures apparaîtront ici dès qu\'elles auront été émises.' : 'Your invoices will appear here once issued.' }}</p>
            </div>
        </div>
    @else
        <div class="card" style="padding:0;overflow:hidden;">
            <div style="padding:20px 28px 0;"><div class="card-title">{{ __('erp.portal.nav.invoices') }}</div></div>
            <div style="overflow-x:auto;">
                <table class="portal-table">
                    <thead>
                        <tr>
                            <th>{{ app()->getLocale() === 'fr' ? 'Facture' : 'Invoice' }}</th>
                            <th>{{ app()->getLocale() === 'fr' ? 'Date d\'émission' : 'Issue date' }}</th>
                            <th>{{ app()->getLocale() === 'fr' ? 'Échéance' : 'Due date' }}</th>
                            <th>{{ app()->getLocale() === 'fr' ? 'Montant' : 'Amount' }}</th>
                            <th>{{ app()->getLocale() === 'fr' ? 'Solde dû' : 'Balance due' }}</th>
                            <th>{{ __('erp.portal.quotes.status') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoices as $invoice)
                        <tr>
                            <td style="font-weight:700;">{{ $invoice->invoice_number }}</td>
                            <td>{{ $invoice->issue_date?->format('d/m/Y') ?? '—' }}</td>
                            <td>{{ $invoice->due_date?->format('d/m/Y') ?? '—' }}</td>
                            <td style="font-weight:700;font-variant-numeric:tabular-nums;">FCFA {{ number_format((float) $invoice->total, 0, '.', ' ') }}</td>
                            <td style="font-weight:700;font-variant-numeric:tabular-nums;color:{{ (float) $invoice->balance_due > 0 ? '#ba1a1a' : '#166534' }};">
                                FCFA {{ number_format((float) $invoice->balance_due, 0, '.', ' ') }}
                            </td>
                            <td>
                                <span class="badge badge-{{ $invoice->status }}">
                                    {{ __('erp.resources.invoice.statuses.' . $invoice->status) ?: $invoice->status }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('portal.invoice', ['token' => $token, 'invoice' => $invoice]) }}" class="btn btn-sm">{{ __('erp.portal.quotes.view') }}</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

<footer>
    <p>{{ $company?->company_name ?: config('app.name') }} · {{ __('erp.portal.secure_portal') }} · {{ __('erp.portal.do_not_share') }}</p>
</footer>
</body>
</html>
