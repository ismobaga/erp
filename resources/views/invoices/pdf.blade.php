<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $invoice->invoice_number }} - Facture</title>
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
            --danger: #ba1a1a;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            /* padding: 32px 16px; */
            padding: 0;
            background: #f8f9ff;
            background: var(--surface);
            color: #0b1c30;
            color: var(--text);
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
        }

        .invoice-container {
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
            background: #ffffff;
            background: var(--surface-card);
            border-radius: 18px;
            box-shadow: 0 20px 60px rgba(14, 31, 63, 0.12);
            overflow: hidden;
            position: relative;
        }

        .invoice-container::before {
            content: '';
            position: absolute;
            inset: 0 auto 0 0;
            width: 8px;
            background: #1a365d;
            background: var(--primary-soft);
        }

        .content {
            padding: 48px;
            position: relative;
            z-index: 2;
        }

        .invoice-watermark {
            position: absolute;
            inset: 0;
            pointer-events: none;
            user-select: none;
            overflow: hidden;
            z-index: 1;
        }

        .watermark-badge {
            position: absolute;
            top: 50%;
            left: 50%;
            opacity: 0.12;
            transform: translate(-50%, -50%) rotate(-28deg);
            border: 14px solid currentColor;
            border-radius: 36px;
            padding: 22px 40px;
            text-align: center;
            text-transform: uppercase;
        }

        .watermark-main {
            font-size: 110px;
            line-height: 0.95;
            font-weight: 900;
            letter-spacing: 0.18em;
            white-space: nowrap;
        }

        .watermark-sub {
            margin-top: 6px;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 0.6em;
            white-space: nowrap;
        }

        .watermark-overdue {
            color: var(--danger);
        }

        .watermark-paid {
            color: #0f766e;
        }

        .watermark-cancelled {
            color: #74777f;
            border-style: dashed;
        }

        .topbar {
            display: table;
            width: 100%;
            table-layout: fixed;
            margin-bottom: 40px;
        }

        .topbar>div {
            display: table-cell;
            vertical-align: top;
        }

        .brand-wrap {
            display: table;
            width: auto;
        }

        .brand-wrap>* {
            display: table-cell;
            vertical-align: top;
        }

        .brand-wrap>*+* {
            padding-left: 16px;
        }

        .brand-icon {
            width: 52px;
            height: 52px;
            line-height: 52px;
            text-align: center;
            border-radius: 12px;
            background: #1a365d;
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
            color: #002045;
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
            color: #57657a;
            color: var(--muted);
        }

        .invoice-word {
            font-size: 56px;
            color: #d3e4fe;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin: 0 0 20px;
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
            font-size: 30px;
            font-weight: 800;
            color: var(--primary);
        }

        .meta-grid {
            display: table;
            width: 100%;
            table-layout: fixed;
            margin-bottom: 36px;
        }

        .meta-grid>div {
            display: table-cell;
            vertical-align: top;
        }

        .meta-grid>div+div {
            padding-left: 24px;
        }

        .panel {
            background: #eff4ff;
            background: var(--surface-low);
            border-radius: 16px;
            padding: 24px;
        }

        .meta-line {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(87, 101, 122, 0.18);
            font-size: 14px;
        }

        .meta-line:last-child {
            border-bottom: none;
        }

        .danger {
            color: var(--danger);
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .items-table thead th {
            background: #1a365d;
            background: var(--primary-soft);
            color: white;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            padding: 14px 16px;
            text-align: left;
        }

        .items-table thead th:nth-child(2) {
            text-align: center;
        }

        .items-table thead th:nth-child(3),
        .items-table thead th:nth-child(4) {
            text-align: right;
        }

        .items-table tbody td {
            padding: 16px;
            border-bottom: 1px solid var(--line);
            vertical-align: top;
            font-size: 14px;
        }

        .items-table tbody tr:nth-child(even) td {
            background: rgba(239, 244, 255, 0.5);
        }

        .items-table tbody td:nth-child(2) {
            text-align: center;
        }

        .items-table tbody td:nth-child(3),
        .items-table tbody td:nth-child(4) {
            text-align: right;
            white-space: nowrap;
        }

        .item-title {
            font-weight: 700;
            margin-bottom: 4px;
        }

        .summary {
            text-align: right;
            margin-top: 28px;
        }

        .summary-box {
            width: 100%;
            max-width: 360px;
            display: inline-block;
            text-align: left;
        }

        .summary-row {
            padding: 8px 0;
            overflow: hidden;
        }

        .summary-row span {
            float: left;
        }

        .summary-row strong {
            float: right;
            text-align: right;
        }

        .grand-total {
            margin-top: 10px;
            background: linear-gradient(135deg, var(--primary-soft), var(--primary));
            color: white;
            border-radius: 16px;
            padding: 18px 20px;
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
        }

        .grand-total strong {
            font-size: 30px;
        }

        .footer-grid {
            display: table;
            width: 100%;
            table-layout: fixed;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--line);
        }

        .footer-grid>div {
            display: table-cell;
            vertical-align: top;
        }

        .footer-grid>div+div {
            padding-left: 28px;
        }

        .small-card {
            background: #eff4ff;
            background: var(--surface-low);
            border-radius: 14px;
            padding: 18px 20px;
        }

        .payment-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .payment-table td {
            padding: 5px 0;
            vertical-align: top;
        }

        .payment-label {
            width: 42%;
            color: #57657a;
            color: var(--muted);
            font-weight: 700;
        }

        .payment-value {
            text-align: right;
            font-weight: 700;
            color: #002045;
            color: var(--primary);
        }

        .closing-note {
            font-size: 12px;
            color: var(--muted);
            line-height: 1.7;
            text-align: right;
        }

        .legal {
            margin-top: 26px;
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
            max-width: 1100px;
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

            .invoice-container {
                max-width: none;
                box-shadow: none !important;
                border-radius: 0;
            }

            .actions {
                display: none;
            }
        }

        @media (max-width: 860px) {
            .content {
                padding: 28px;
            }

            .topbar,
            .meta-grid,
            .footer-grid {
                grid-template-columns: 1fr;
                display: grid;
            }

            .invoice-word {
                font-size: 36px;
            }

            .items-table {
                font-size: 12px;
            }
        }
    </style>
</head>

<body>
    @php
        $currency = $company?->currency ?: 'FCFA';
        $formatMoney = fn($amount) => number_format((float) $amount, 0, ',', ' ') . ' ' . $currency;
        $clientName = $invoice->client?->company_name ?: $invoice->client?->contact_name ?: 'Client';
        $companyName = $company?->company_name ?: 'CROMMIX MALI S.A.';
        $companyAddress = array_filter([$company?->address, $company?->city, $company?->country]);
        $clientAddress = array_filter([$invoice->client?->address, $invoice->client?->city, $invoice->client?->country]);
        $notes = $invoice->notes ?: $company?->invoice_default_notes;
        $downloadUrl = route('invoices.pdf', ['invoice' => $invoice, 'download' => 1]);
        $statusLabel = match ($invoice->status) {
            'draft' => 'Brouillon',
            'sent' => 'Envoyée',
            'partially_paid' => 'Partiellement payée',
            'paid' => 'Payée',
            'overdue' => 'En retard',
            'cancelled' => 'Annulée',
            default => ucfirst((string) $invoice->status),
        };
        $watermark = match ($invoice->status) {
            'overdue' => ['main' => 'EN RETARD', 'sub' => 'OVERDUE', 'class' => 'watermark-overdue'],
            'paid' => ['main' => 'PAYÉE', 'sub' => 'PAID', 'class' => 'watermark-paid'],
            'cancelled' => ['main' => 'ANNULÉE', 'sub' => 'VOID', 'class' => 'watermark-cancelled'],
            default => null,
        };
    @endphp

    <div class="invoice-container">
        @if($watermark)
            <div class="invoice-watermark" aria-hidden="true">
                <div class="watermark-badge {{ $watermark['class'] }}">
                    <div class="watermark-main">{{ $watermark['main'] }}</div>
                    <div class="watermark-sub">{{ $watermark['sub'] }}</div>
                </div>
            </div>
        @endif
        <div class="content">
            <header class="topbar">
                <div>
                    <div class="brand-wrap">
                        @if(!empty($logoDataUri))
                            <img src="{{ $logoDataUri }}" alt="Logo {{ $companyName }}" class="brand-logo">
                        @else
                            <div class="brand-icon">CM</div>
                        @endif
                        <div>
                            <h1 class="brand-title">{{ $companyName }}</h1>
                            <div class="brand-subtitle">Excellence architecturale</div>
                        </div>
                    </div>
                    <div class="muted" style="font-size: 14px; line-height: 1.7; margin-top: 16px;">
                        @forelse($companyAddress as $line)
                            <div>{{ $line }}</div>
                        @empty
                            <div>Zone Industrielle, Rue 14</div>
                            <div>Bamako, Mali</div>
                        @endforelse
                        @if($company?->phone)
                            <div style="margin-top: 8px;"><strong>Téléphone :</strong> {{ $company->phone }}</div>
                        @endif
                        @if($company?->email)
                            <div><strong>E-mail :</strong> {{ $company->email }}</div>
                        @endif
                        @if($company?->website)
                            <div><strong>Site web :</strong> {{ $company->website }}</div>
                        @endif
                    </div>
                </div>

                <div style="text-align: right;">
                    <p class="invoice-word">Facture</p>
                    <div class="doc-number-label">Numéro de facture</div>
                    <div class="doc-number-value">{{ $invoice->invoice_number }}</div>
                </div>
            </header>

            <section class="meta-grid">
                <div class="panel">
                    <div class="section-label" style="margin-bottom: 12px;">Facturer à</div>
                    <div style="font-size: 24px; font-weight: 800; color: var(--text);">{{ $clientName }}</div>
                    <div class="muted" style="font-size: 14px; line-height: 1.7; margin-top: 10px;">
                        @forelse($clientAddress as $line)
                            <div>{{ $line }}</div>
                        @empty
                            <div>Adresse de facturation non renseignée</div>
                        @endforelse
                        @if($invoice->client?->email)
                            <div style="margin-top: 8px;"><strong>E-mail :</strong> {{ $invoice->client->email }}</div>
                        @endif
                        @if($invoice->client?->phone)
                            <div><strong>Téléphone :</strong> {{ $invoice->client->phone }}</div>
                        @endif
                    </div>
                </div>

                <div>
                    <div class="meta-line">
                        <span class="section-label">Date d'émission</span>
                        <strong>{{ optional($invoice->issue_date)?->translatedFormat('d F Y') ?: '—' }}</strong>
                    </div>
                    <div class="meta-line">
                        <span class="section-label">Date d'échéance</span>
                        <strong
                            class="{{ $invoice->status === 'overdue' ? 'danger' : '' }}">{{ optional($invoice->due_date)?->translatedFormat('d F Y') ?: '—' }}</strong>
                    </div>
                    <div class="meta-line">
                        <span class="section-label">Référence projet</span>
                        <strong>{{ $invoice->quote?->quote_number ?: $invoice->invoice_number }}</strong>
                    </div>
                    <div class="meta-line">
                        <span class="section-label">Statut</span>
                        <strong>{{ $statusLabel }}</strong>
                    </div>
                </div>
            </section>

            <section>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Quantité</th>
                            <th>Prix unitaire</th>
                            <th>Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoice->items as $item)
                            <tr>
                                <td>
                                    <div class="item-title">
                                        {{ $item->description ?: $item->service?->name ?: 'Ligne de facture' }}
                                    </div>
                                    @if($item->service?->name && $item->description !== $item->service->name)
                                        <div class="muted" style="font-size: 12px;">Service : {{ $item->service->name }}</div>
                                    @endif
                                </td>
                                <td>{{ rtrim(rtrim(number_format((float) $item->quantity, 2, '.', ''), '0'), '.') }}</td>
                                <td>{{ $formatMoney($item->unit_price) }}</td>
                                <td><strong style="color: var(--primary);">{{ $formatMoney($item->line_total) }}</strong>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="muted" style="text-align: center;">Aucune ligne de facture
                                    disponible.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </section>

            <section class="summary">
                <div class="summary-box">
                    <div class="summary-row">
                        <span class="muted">Sous-total</span>
                        <strong>{{ $formatMoney($invoice->subtotal) }}</strong>
                    </div>
                    <div class="summary-row">
                        <span class="muted">Remise</span>
                        <strong>{{ $formatMoney($invoice->discount_total) }}</strong>
                    </div>
                    <div class="summary-row">
                        <span class="muted">Taxes</span>
                        <strong>{{ $formatMoney($invoice->tax_total) }}</strong>
                    </div>
                    <div class="summary-row">
                        <span class="muted">Montant payé</span>
                        <strong>{{ $formatMoney($invoice->paid_total) }}</strong>
                    </div>
                    <div class="summary-row">
                        <span class="muted">Reste à payer</span>
                        <strong>{{ $formatMoney($invoice->balance_due) }}</strong>
                    </div>
                    <div class="grand-total">
                        <span
                            style="text-transform: uppercase; letter-spacing: 0.14em; font-size: 12px; font-weight: 800;">Total
                            TTC</span>
                        <strong>{{ $formatMoney($invoice->total) }}</strong>
                    </div>
                </div>
            </section>

            <footer class="footer-grid">
                <div>
                    <div class="section-label" style="margin-bottom: 12px;">Instructions de paiement</div>
                    <div class="small-card">
                        <table class="payment-table" role="presentation">
                            <tr>
                                <td class="payment-label">Banque</td>
                                <td class="payment-value">{{ $bankDetails['bank_name'] }}</td>
                            </tr>
                            <tr>
                                <td class="payment-label">Titulaire</td>
                                <td class="payment-value">{{ $bankDetails['account_name'] }}</td>
                            </tr>
                            <tr>
                                <td class="payment-label">Numéro de compte (RIB)</td>
                                <td class="payment-value">{{ $bankDetails['account_number'] }}</td>
                            </tr>
                            <tr>
                                <td class="payment-label">Code SWIFT</td>
                                <td class="payment-value">{{ $bankDetails['swift_code'] }}</td>
                            </tr>
                            <tr>
                                <td class="payment-label">Référence</td>
                                <td class="payment-value">{{ $invoice->invoice_number }}</td>
                            </tr>
                            <tr>
                                <td class="payment-label">Montant dû</td>
                                <td class="payment-value">{{ $formatMoney($invoice->balance_due) }}</td>
                            </tr>
                            @if($company?->email)
                                <tr>
                                    <td class="payment-label">Contact facturation</td>
                                    <td class="payment-value">{{ $company->email }}</td>
                                </tr>
                            @endif
                        </table>
                    </div>
                </div>

                <div>
                    <div class="section-label" style="margin-bottom: 12px; text-align: right;">Conditions générales
                    </div>
                    <div class="closing-note">
                        Merci d'indiquer le numéro de facture sur tous vos virements.<br>
                        Le paiement est exigible sous 30 jours sauf accord contraire.<br>
                        {{ $notes ?: 'Merci pour votre confiance et votre collaboration avec Crommix Mali.' }}
                    </div>
                </div>
            </footer>

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
            <button class="button button-primary" onclick="window.print()">Imprimer la facture</button>
            <a href="{{ $downloadUrl }}" class="button button-secondary button-link">Télécharger le PDF</a>
        </div>
    @endunless
</body>

</html>