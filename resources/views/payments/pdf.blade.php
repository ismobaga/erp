<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu de paiement - {{ $payment->reference ?: '#' . $payment->id }}</title>
    <style>
        @page {
            margin: 0mm;
        }

        :root {
            --primary: #002045;
            --primary-soft: #1a365d;
            --surface: #f8f9ff;
            --surface-low: #eff4ff;
            --surface-card: #ffffff;
            --text: #0b1c30;
            --muted: #57657a;
            --line: #d3e4fe;
            --success: #0f766e;
            --danger: #ba1a1a;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            background: var(--surface);
            color: var(--text);
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
        }

        .doc-container {
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            background: var(--surface-card);
            border-radius: 18px;
            box-shadow: 0 20px 60px rgba(14, 31, 63, 0.12);
            overflow: hidden;
            position: relative;
        }

        .doc-container::before {
            content: '';
            position: absolute;
            inset: 0 auto 0 0;
            width: 8px;
            background: var(--success);
        }

        .content {
            padding: 48px;
            position: relative;
            z-index: 2;
        }

        .receipt-badge {
            display: inline-block;
            background: var(--success);
            color: white;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            border-radius: 999px;
            padding: 6px 18px;
            margin-bottom: 32px;
        }

        .topbar {
            width: 100%;
            margin-bottom: 40px;
            font-size: 0;
        }

        .topbar>div {
            display: inline-block;
            width: 50%;
            vertical-align: top;
            font-size: 14px;
        }

        .brand-wrap>* {
            display: inline-block;
            vertical-align: top;
        }

        .brand-wrap>*+* {
            margin-left: 16px;
        }

        .brand-icon {
            width: 52px;
            height: 52px;
            line-height: 52px;
            text-align: center;
            border-radius: 12px;
            background: var(--primary-soft);
            color: white;
            display: inline-block;
            font-size: 24px;
            font-weight: 700;
        }

        .brand-logo {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            object-fit: cover;
            border: 1px solid var(--line);
            background: white;
        }

        .brand-title {
            margin: 0;
            font-size: 28px;
            line-height: 1.1;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--primary);
        }

        .brand-subtitle {
            margin: 6px 0 16px;
            font-size: 11px;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: var(--muted);
            font-weight: 700;
        }

        .muted {
            color: var(--muted);
        }

        .doc-word {
            font-size: 46px;
            color: #d3e4fe;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin: 0 0 16px;
        }

        .doc-number-label,
        .section-label {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .doc-number-value {
            margin-top: 8px;
            font-size: 26px;
            font-weight: 800;
            color: var(--primary);
        }

        .amount-hero {
            background: linear-gradient(135deg, var(--success), #065f46);
            border-radius: 20px;
            padding: 32px 36px;
            margin: 32px 0;
            color: white;
            width: 100%;
            font-size: 0;
        }

        .amount-hero>div {
            display: inline-block;
            width: 50%;
            vertical-align: middle;
            font-size: 14px;
        }

        .amount-label {
            font-size: 13px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            opacity: 0.85;
            margin-bottom: 10px;
        }

        .amount-value {
            font-size: 48px;
            font-weight: 900;
            line-height: 1;
        }

        .amount-date {
            text-align: right;
            font-size: 13px;
            opacity: 0.85;
        }

        .details-grid {
            width: 100%;
            margin-top: 28px;
            font-size: 0;
        }

        .details-grid>div {
            display: inline-block;
            width: 50%;
            vertical-align: top;
            padding-right: 12px;
            padding-left: 12px;
            font-size: 14px;
        }

        .details-grid>div:first-child {
            padding-left: 0;
        }

        .details-grid>div:last-child {
            padding-right: 0;
        }

        .detail-card {
            background: var(--surface-low);
            border-radius: 14px;
            padding: 20px 22px;
        }

        .detail-card .section-label {
            display: block;
            margin-bottom: 14px;
        }

        .detail-row {
            display: block;
            padding: 8px 0;
            border-bottom: 1px solid rgba(87, 101, 122, 0.15);
            font-size: 14px;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-row .label {
            color: var(--muted);
            display: inline-block;
            width: 46%;
            vertical-align: top;
        }

        .detail-row .value {
            font-weight: 700;
            color: var(--primary);
            text-align: right;
            display: inline-block;
            width: 54%;
            vertical-align: top;
        }

        .invoice-ref-box {
            background: var(--surface-low);
            border-radius: 14px;
            padding: 20px 24px;
            margin-top: 24px;
            width: 100%;
            font-size: 0;
        }

        .invoice-ref-box>div {
            display: inline-block;
            width: 50%;
            vertical-align: middle;
            font-size: 14px;
        }

        .method-pill {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            background: #dbeafe;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .flagged-banner {
            background: #fee2e2;
            color: #7f1d1d;
            border: 1px solid #fca5a5;
            border-radius: 12px;
            padding: 14px 18px;
            margin-top: 20px;
            font-size: 13px;
            font-weight: 700;
        }

        .footer-note {
            margin-top: 36px;
            padding-top: 20px;
            border-top: 1px solid var(--line);
            font-size: 12px;
            color: var(--muted);
            line-height: 1.7;
            text-align: center;
        }

        .legal {
            margin-top: 12px;
            text-align: center;
            font-size: 10px;
            letter-spacing: 0.12em;
            color: #7b8595;
            text-transform: uppercase;
        }

        .actions {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin: 24px auto 0;
            max-width: 900px;
        }

        .button {
            border: 0;
            border-radius: 999px;
            padding: 14px 22px;
            font-weight: 700;
            cursor: pointer;
        }

        .button-primary {
            background: var(--primary);
            color: white;
        }

        .button-secondary {
            background: #dce9ff;
            color: var(--primary);
        }

        .button-link {
            display: inline-block;
            text-decoration: none;
        }

        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            body {
                padding: 0;
                background: white !important;
            }

            .doc-container {
                max-width: none;
                box-shadow: none !important;
                border-radius: 0;
            }

            .actions {
                display: none;
            }
        }
    </style>
</head>

<body>
    @php
        $currency = $company?->currency ?: 'FCFA';
        $formatMoney = fn($amount) => number_format((float) $amount, 0, ',', ' ') . ' ' . $currency;
        $companyName = $company?->company_name ?: config('app.name');
        $companyAddress = array_filter([$company?->address, $company?->city, $company?->country]);
        $client = $payment->invoice?->client;
        $clientName = $client?->company_name ?: $client?->contact_name ?: 'Client';
        $downloadUrl = route('payments.pdf', ['payment' => $payment, 'download' => 1]);

        $methodLabel = match (strtolower((string) $payment->payment_method)) {
            'cash' => 'Espèces',
            'bank_transfer' => 'Virement bancaire',
            'check' => 'Chèque',
            'mobile_money' => 'Mobile Money' . ($payment->mobile_money_operator ? ' (' . $payment->mobile_money_operator . ')' : ''),
            'card' => 'Carte bancaire',
            default => ucfirst(str_replace('_', ' ', (string) $payment->payment_method)),
        };
    @endphp

    <div class="doc-container">
        <div class="content">
            <header class="topbar">
                <div>
                    <div class="brand-wrap">
                        @if(!empty($logoDataUri))
                            <img src="{{ $logoDataUri }}" alt="Logo {{ $companyName }}" class="brand-logo">
                        @else
                            <div class="brand-icon">{{ mb_strtoupper(mb_substr($companyName, 0, 2)) }}</div>
                        @endif
                        <div>
                            <h1 class="brand-title">{{ $companyName }}</h1>
                            <div class="brand-subtitle">Gestion financière &amp; ERP</div>
                        </div>
                    </div>
                    <div class="muted" style="font-size: 14px; line-height: 1.7; margin-top: 16px;">
                        @forelse($companyAddress as $line)
                            <div>{{ $line }}</div>
                        @empty
                            <div>Adresse non renseignée</div>
                        @endforelse
                        @if($company?->email)
                            <div style="margin-top: 8px;"><strong>E-mail :</strong> {{ $company->email }}</div>
                        @endif
                    </div>
                </div>

                <div style="text-align: right;">
                    <p class="doc-word">Reçu</p>
                    <div class="doc-number-label">Référence</div>
                    <div class="doc-number-value">{{ $payment->reference ?: '#' . $payment->id }}</div>
                </div>
            </header>

            <span class="receipt-badge">✓ Paiement reçu</span>

            <div class="amount-hero">
                <div>
                    <div class="amount-label">Montant encaissé</div>
                    <div class="amount-value">{{ $formatMoney($payment->amount) }}</div>
                </div>
                <div>
                    <div class="amount-date" style="text-align: right;">
                        <div class="section-label" style="color: rgba(255,255,255,0.75); margin-bottom: 8px;">Date</div>
                        <div style="font-size: 20px; font-weight: 800;">
                            {{ optional($payment->payment_date)?->translatedFormat('d F Y') ?: '—' }}
                        </div>
                    </div>
                </div>
            </div>

            @if($payment->is_flagged)
                <div class="flagged-banner">
                    ⚠ Ce paiement est signalé comme suspect.
                    @if($payment->flagged_reason)
                        Motif : {{ $payment->flagged_reason }}
                    @endif
                </div>
            @endif

            <div class="details-grid">
                <div>
                    <div class="detail-card">
                        <span class="section-label">Payé par</span>
                        <div style="font-size: 20px; font-weight: 800; color: var(--text); margin-bottom: 12px;">
                            {{ $clientName }}
                        </div>
                        @if($client?->email)
                            <div class="detail-row">
                                <span class="label">E-mail</span>
                                <span class="value">{{ $client->email }}</span>
                            </div>
                        @endif
                        @if($client?->phone)
                            <div class="detail-row">
                                <span class="label">Téléphone</span>
                                <span class="value">{{ $client->phone }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <div>
                    <div class="detail-card">
                        <span class="section-label">Détails du paiement</span>
                        <div class="detail-row">
                            <span class="label">Mode de paiement</span>
                            <span class="value">
                                <span class="method-pill">{{ $methodLabel }}</span>
                            </span>
                        </div>
                        @if($payment->reference)
                            <div class="detail-row">
                                <span class="label">Référence</span>
                                <span class="value">{{ $payment->reference }}</span>
                            </div>
                        @endif
                        <div class="detail-row">
                            <span class="label">Montant</span>
                            <span class="value">{{ $formatMoney($payment->amount) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            @if($payment->invoice)
                <div class="invoice-ref-box">
                    <div>
                        <div class="section-label" style="display: block; margin-bottom: 8px;">Facture associée</div>
                        <div style="font-size: 22px; font-weight: 800; color: var(--primary);">
                            {{ $payment->invoice->invoice_number }}
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div class="section-label" style="display: block; margin-bottom: 8px;">Solde restant</div>
                        <div style="font-size: 18px; font-weight: 800; color: var(--primary);">
                            {{ $formatMoney($payment->invoice->balance_due) }}
                        </div>
                    </div>
                </div>
            @endif

            @if($payment->notes)
                <div style="margin-top: 24px; font-size: 13px; color: var(--muted); line-height: 1.7;">
                    <strong>Notes :</strong> {{ strip_tags((string) $payment->notes) }}
                </div>
            @endif

            <div class="footer-note">
                Ce reçu atteste que le paiement ci-dessus a été enregistré par {{ $companyName }}.<br>
                Conservez ce document à titre de justificatif.
            </div>

            <div class="legal">
                © {{ now()->year }} {{ $companyName }}
                @if($company?->tax_number)
                    | NIF : {{ $company->tax_number }}
                @endif
            </div>
        </div>
    </div>

    @unless($isDownload ?? false)
        <div class="actions">
            <button class="button button-primary" onclick="window.print()">Imprimer le reçu</button>
            <a href="{{ $downloadUrl }}" class="button button-secondary button-link">Télécharger le PDF</a>
        </div>
    @endunless
</body>

</html>