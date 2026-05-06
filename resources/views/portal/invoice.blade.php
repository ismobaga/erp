<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture {{ $invoice->invoice_number }}</title>
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f4f7fb;
            color: #002045;
            min-height: 100vh;
        }

        .topbar {
            background: linear-gradient(135deg, #002045 0%, #1a365d 100%);
            padding: 20px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .topbar-brand {
            color: #fff;
            font-size: 18px;
            font-weight: 800;
        }

        .topbar-sub {
            color: rgba(255, 255, 255, 0.6);
            font-size: 12px;
            margin-top: 2px;
        }

        .topbar-actions {
            display: flex;
            gap: 8px;
            flex-shrink: 0;
        }

        a.btn {
            display: inline-block;
            padding: 8px 18px;
            background: #fff;
            color: #002045;
            border-radius: 8px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 800;
        }

        a.btn-outline {
            background: transparent;
            color: #fff;
            border: 2px solid rgba(255, 255, 255, 0.5);
        }

        a.btn:hover {
            opacity: 0.88;
        }

        .container {
            max-width: 860px;
            margin: 0 auto;
            padding: 32px 16px;
        }

        .card {
            background: #fff;
            border-radius: 16px;
            padding: 32px;
            margin-bottom: 24px;
            box-shadow: 0 2px 12px rgba(0, 32, 69, 0.07);
        }

        .card-title {
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #57657a;
            margin-bottom: 16px;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        @media (max-width:600px) {
            .meta-grid {
                grid-template-columns: 1fr;
            }
        }

        .meta-label {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #57657a;
            margin-bottom: 4px;
        }

        .meta-value {
            font-size: 15px;
            font-weight: 700;
            color: #002045;
        }

        .badge {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .badge-draft {
            background: #f1f5f9;
            color: #57657a;
        }

        .badge-sent {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .badge-paid {
            background: #dcfce7;
            color: #166534;
        }

        .badge-overdue {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-partially_paid {
            background: #e0f2fe;
            color: #0369a1;
        }

        .badge-cancelled {
            background: #f1f5f9;
            color: #6b7280;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
        }

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

        table.items tfoot td {
            font-weight: 700;
            padding-top: 14px;
        }

        .total-row td {
            font-size: 16px;
            font-weight: 900;
            color: #002045;
        }

        .balance-due {
            color: #ba1a1a !important;
        }

        .balance-zero {
            color: #166534 !important;
        }

        .payments-list {
            list-style: none;
        }

        .payments-list li {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f4f9;
            font-size: 14px;
        }

        .payments-list li:last-child {
            border-bottom: none;
        }

        footer {
            text-align: center;
            padding: 32px 16px;
            font-size: 12px;
            color: #aab0bc;
        }

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
    </style>
</head>

<body>
    @php
        $companyDisplayName = $company?->name ?: $company?->company_name ?: config('app.name');
        $statusLabel = match ($invoice->status) {
            'draft' => 'Brouillon',
            'sent' => 'Envoyée',
            'paid' => 'Payée',
            'overdue' => 'En retard',
            'partially_paid' => 'Partiellement payée',
            'cancelled' => 'Annulée',
            default => $invoice->status,
        };
    @endphp

    <div class="topbar">
        <div>
            @if(!empty($logoDataUri))
                <div style="margin-bottom:8px;">
                    <img src="{{ $logoDataUri }}" alt="{{ $companyDisplayName }}"
                        style="max-height:40px; max-width:180px; display:block;">
                </div>
            @endif
            <div class="topbar-brand">{{ $companyDisplayName }}</div>
            <div class="topbar-sub">Facture {{ $invoice->invoice_number }}</div>
        </div>
        <div class="topbar-actions">
            <a href="{{ route('portal.index', ['token' => $token]) }}" class="btn btn-outline">← Retour</a>
            <a href="{{ route('portal.invoice.pdf', ['token' => $token, 'invoice' => $invoice]) }}" class="btn">⬇
                Télécharger PDF</a>
        </div>
    </div>

    <div class="container">

        {{-- Identity --}}
        <div class="card">
            <div class="card-title">Détails de la facture</div>
            <div class="meta-grid">
                <div>
                    <div class="meta-label">Numéro de facture</div>
                    <div class="meta-value">{{ $invoice->invoice_number }}</div>
                </div>
                <div>
                    <div class="meta-label">Statut</div>
                    <div class="meta-value">
                        <span class="badge badge-{{ $invoice->status }}">
                            {{ $statusLabel }}
                        </span>
                    </div>
                </div>
                <div>
                    <div class="meta-label">Date d'émission</div>
                    <div class="meta-value">{{ $invoice->issue_date?->format('d/m/Y') ?? '—' }}</div>
                </div>
                <div>
                    <div class="meta-label">Date d'échéance</div>
                    <div class="meta-value">{{ $invoice->due_date?->format('d/m/Y') ?? '—' }}</div>
                </div>
                <div>
                    <div class="meta-label">Montant total</div>
                    <div class="meta-value" style="font-size:20px;">FCFA
                        {{ number_format((float) $invoice->total, 0, '.', ' ') }}</div>
                </div>
                <div>
                    <div class="meta-label">Solde restant dû</div>
                    <div class="meta-value {{ (float) $invoice->balance_due > 0 ? 'balance-due' : 'balance-zero' }}"
                        style="font-size:20px;">
                        FCFA {{ number_format((float) $invoice->balance_due, 0, '.', ' ') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Line items --}}
        @if($invoice->items->isNotEmpty())
            <div class="card">
                <div class="card-title">Détail des prestations</div>
                <table class="items">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th style="text-align:right;">Qté</th>
                            <th style="text-align:right;">Prix unitaire</th>
                            <th style="text-align:right;">Total ligne</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->items as $item)
                            <tr>
                                <td>{{ $item->description }}</td>
                                <td style="text-align:right;">{{ number_format((float) $item->quantity, 2, ',', ' ') }}</td>
                                <td style="text-align:right;">FCFA {{ number_format((float) $item->unit_price, 0, '.', ' ') }}
                                </td>
                                <td style="text-align:right;font-weight:700;">FCFA
                                    {{ number_format((float) $item->line_total, 0, '.', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align:right;">Sous-total</td>
                            <td style="text-align:right;">FCFA {{ number_format((float) $invoice->subtotal, 0, '.', ' ') }}
                            </td>
                        </tr>
                        @if((float) $invoice->tax_total > 0)
                            <tr>
                                <td colspan="3" style="text-align:right;">Taxes</td>
                                <td style="text-align:right;">FCFA {{ number_format((float) $invoice->tax_total, 0, '.', ' ') }}
                                </td>
                            </tr>
                        @endif
                        @if((float) $invoice->discount_total > 0)
                            <tr>
                                <td colspan="3" style="text-align:right;">Remise</td>
                                <td style="text-align:right; color:#166534;">− FCFA
                                    {{ number_format((float) $invoice->discount_total, 0, '.', ' ') }}</td>
                            </tr>
                        @endif
                        <tr class="total-row">
                            <td colspan="3" style="text-align:right;">Total à payer</td>
                            <td style="text-align:right;">FCFA {{ number_format((float) $invoice->total, 0, '.', ' ') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif

        {{-- Payments --}}
        @if($invoice->payments->isNotEmpty())
            <div class="card">
                <div class="card-title">Historique des paiements</div>
                <ul class="payments-list">
                    @foreach($invoice->payments as $payment)
                                <li>
                                    <span>
                                        {{ $payment->payment_date?->format('d/m/Y') ?? '—' }}
                                        · {{ match ($payment->payment_method) {
                            'bank_transfer' => 'Virement bancaire',
                            'cash' => 'Espèces',
                            'check' => 'Chèque',
                            'mobile_money' => 'Mobile money' . ($payment->mobile_money_operator ? ' (' . $payment->mobile_money_operator . ')' : ''),
                            default => ucfirst($payment->payment_method),
                        } }}
                                        @if($payment->reference) · Réf : {{ $payment->reference }} @endif
                                    </span>
                                    <span style="font-weight:700; color:#166534;">FCFA
                                        {{ number_format((float) $payment->amount, 0, '.', ' ') }}</span>
                                </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Bank details --}}
        @if($bankDetails['bank_name'] || $bankDetails['account_number'])
            <div class="card">
                <div class="card-title">Informations de paiement</div>
                <div class="bank-box">
                    <p>
                        @if($bankDetails['bank_name'])<strong>Banque :</strong> {{ $bankDetails['bank_name'] }}<br>@endif
                        @if($bankDetails['account_name'])<strong>Intitulé :</strong>
                        {{ $bankDetails['account_name'] }}<br>@endif
                        @if($bankDetails['account_number'])<strong>Numéro de compte :</strong>
                        {{ $bankDetails['account_number'] }}<br>@endif
                        @if($bankDetails['swift_code'])<strong>SWIFT / BIC :</strong> {{ $bankDetails['swift_code'] }}@endif
                    </p>
                </div>
            </div>
        @endif

        {{-- Notes --}}
        @if($invoice->notes)
            <div class="card">
                <div class="card-title">Notes</div>
                <p style="font-size:14px; color:#374151; line-height:1.7; white-space:pre-line;">{{ $invoice->notes }}</p>
            </div>
        @endif

    </div>

    <footer>
        <p>{{ $companyDisplayName }} · Portail client sécurisé · Ne partagez pas ce lien.</p>
    </footer>
</body>

</html>