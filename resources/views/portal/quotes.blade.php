<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('erp.portal.quotes.title') }} — {{ $client->company_name ?: $client->contact_name }}</title>
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

    @if($quotes->isEmpty())
        <div class="card">
            <div class="empty">
                <div class="empty-icon">📋</div>
                <p style="font-size:16px;font-weight:700;margin-bottom:8px;">{{ __('erp.portal.quotes.empty') }}</p>
                <p>{{ __('erp.portal.quotes.empty_hint') }}</p>
            </div>
        </div>
    @else
        <div class="card" style="padding:0;overflow:hidden;">
            <div style="padding:20px 28px 0;"><div class="card-title">{{ __('erp.portal.quotes.title') }}</div></div>
            <div style="overflow-x:auto;">
                <table class="portal-table">
                    <thead>
                        <tr>
                            <th>{{ __('erp.portal.quotes.number') }}</th>
                            <th>{{ __('erp.portal.quotes.issue_date') }}</th>
                            <th>{{ __('erp.portal.quotes.valid_until') }}</th>
                            <th>{{ __('erp.portal.quotes.total') }}</th>
                            <th>{{ __('erp.portal.quotes.status') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($quotes as $quote)
                        <tr>
                            <td style="font-weight:700;">{{ $quote->quote_number }}</td>
                            <td>{{ $quote->issue_date?->format('d/m/Y') ?? '—' }}</td>
                            <td>{{ $quote->valid_until?->format('d/m/Y') ?? '—' }}</td>
                            <td style="font-weight:700;font-variant-numeric:tabular-nums;">FCFA {{ number_format((float) $quote->total, 0, '.', ' ') }}</td>
                            <td>
                                <span class="badge badge-{{ $quote->status }}">
                                    {{ __('erp.portal.quotes.statuses.' . $quote->status) ?: $quote->status }}
                                </span>
                            </td>
                            <td style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                <a href="{{ route('portal.quote', ['token' => $token, 'quote' => $quote]) }}" class="btn btn-sm">{{ __('erp.portal.quotes.view') }}</a>
                                @if($quote->canBeAccepted())
                                    <form method="POST" action="{{ route('portal.quote.approve', ['token' => $token, 'quote' => $quote]) }}"
                                          onsubmit="return confirm('{{ __('erp.portal.quotes.confirm_approve') }}')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">{{ __('erp.portal.quotes.approve') }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('portal.quote.reject', ['token' => $token, 'quote' => $quote]) }}"
                                          onsubmit="return confirm('{{ __('erp.portal.quotes.confirm_reject') }}')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-danger">{{ __('erp.portal.quotes.reject') }}</button>
                                    </form>
                                @endif
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
