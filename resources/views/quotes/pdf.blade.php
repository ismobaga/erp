<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $quote->quote_number }} - Devis</title>
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
            padding: 0;
            background: var(--surface);
            color: var(--text);
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
        }

        .doc-container {
            width: 100%;
            max-width: 1100px;
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
            background: var(--primary-soft);
        }

        .content {
            padding: 48px;
            position: relative;
            z-index: 2;
        }

        .doc-watermark {
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

        .watermark-accepted {
            color: #0f766e;
        }

        .watermark-rejected {
            color: var(--danger);
        }

        .watermark-expired {
            color: #74777f;
            border-style: dashed;
        }

        .watermark-converted {
            color: #6d28d9;
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

        .validity-banner {
            margin-top: 28px;
            border-radius: 14px;
            padding: 18px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 14px;
        }

        .validity-banner.valid {
            background: #dcfce7;
            color: #14532d;
            border: 1px solid #86efac;
        }

        .validity-banner.expiring {
            background: #fef9c3;
            color: #713f12;
            border: 1px solid #fde047;
        }

        .validity-banner.expired {
            background: #fee2e2;
            color: #7f1d1d;
            border: 1px solid #fca5a5;
        }

        .footer-note {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--line);
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
        $clientName = $quote->client?->company_name ?: $quote->client?->contact_name ?: 'Client';
        $companyName = $company?->company_name ?: config('app.name');
        $companyAddress = array_filter([$company?->address, $company?->city, $company?->country]);
        $clientAddress = array_filter([$quote->client?->address, $quote->client?->city, $quote->client?->country]);
        $notes = $quote->notes ?: $company?->invoice_default_notes;
        $downloadUrl = route('quotes.pdf', ['quote' => $quote, 'download' => 1]);

        $statusLabel = match ($quote->status) {
            'draft' => 'Brouillon',
            'sent' => 'Envoyé',
            'accepted' => 'Accepté',
            'rejected' => 'Refusé',
            'expired' => 'Expiré',
            'converted' => 'Converti en facture',
            default => ucfirst((string) $quote->status),
        };

        $watermark = match ($quote->status) {
            'accepted' => ['main' => 'ACCEPTÉ', 'sub' => 'ACCEPTED', 'class' => 'watermark-accepted'],
            'rejected' => ['main' => 'REFUSÉ', 'sub' => 'REJECTED', 'class' => 'watermark-rejected'],
            'expired' => ['main' => 'EXPIRÉ', 'sub' => 'EXPIRED', 'class' => 'watermark-expired'],
            'converted' => ['main' => 'CONVERTI', 'sub' => 'CONVERTED', 'class' => 'watermark-converted'],
            default => null,
        };

        $validUntil = $quote->valid_until;
        $today = now()->startOfDay();
        $validityClass = 'valid';
        if ($validUntil) {
            $daysLeft = $today->diffInDays($validUntil, false);
            if ($daysLeft < 0) {
                $validityClass = 'expired';
            } elseif ($daysLeft <= 5) {
                $validityClass = 'expiring';
            }
        }
    @endphp

    <div class="doc-container">
        @if($watermark)
            <div class="doc-watermark" aria-hidden="true">
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
                        @if($company?->phone)
                            <div style="margin-top: 8px;"><strong>Téléphone :</strong> {{ $company->phone }}</div>
                        @endif
                        @if($company?->email)
                            <div><strong>E-mail :</strong> {{ $company->email }}</div>
                        @endif
                    </div>
                </div>

                <div style="text-align: right;">
                    <p class="doc-word">Devis</p>
                    <div class="doc-number-label">Numéro de devis</div>
                    <div class="doc-number-value">{{ $quote->quote_number }}</div>
                </div>
            </header>

            <section class="meta-grid">
                <div class="panel">
                    <div class="section-label" style="margin-bottom: 12px;">Client</div>
                    <div style="font-size: 24px; font-weight: 800; color: var(--text);">{{ $clientName }}</div>
                    <div class="muted" style="font-size: 14px; line-height: 1.7; margin-top: 10px;">
                        @forelse($clientAddress as $line)
                            <div>{{ $line }}</div>
                        @empty
                            <div>Adresse non renseignée</div>
                        @endforelse
                        @if($quote->client?->email)
                            <div style="margin-top: 8px;"><strong>E-mail :</strong> {{ $quote->client->email }}</div>
                        @endif
                        @if($quote->client?->phone)
                            <div><strong>Téléphone :</strong> {{ $quote->client->phone }}</div>
                        @endif
                    </div>
                </div>

                <div>
                    <div class="meta-line">
                        <span class="section-label">Date d'émission</span>
                        <strong>{{ optional($quote->issue_date)?->translatedFormat('d F Y') ?: '—' }}</strong>
                    </div>
                    <div class="meta-line">
                        <span class="section-label">Valide jusqu'au</span>
                        <strong
                            class="{{ ($quote->status === 'expired' || ($validUntil && $validUntil->lt($today))) ? 'danger' : '' }}">
                            {{ optional($validUntil)?->translatedFormat('d F Y') ?: '—' }}
                        </strong>
                    </div>
                    <div class="meta-line">
                        <span class="section-label">Statut</span>
                        <strong>{{ $statusLabel }}</strong>
                    </div>
                    @if($quote->invoice)
                        <div class="meta-line">
                            <span class="section-label">Facture associée</span>
                            <strong>{{ $quote->invoice->invoice_number }}</strong>
                        </div>
                    @endif
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
                        @forelse($quote->items as $item)
                            <tr>
                                <td>
                                    <div class="item-title">
                                        {{ $item->description ?: $item->service?->name ?: 'Ligne de devis' }}
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
                                <td colspan="4" class="muted" style="text-align: center;">Aucune ligne de devis disponible.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </section>

            <section class="summary">
                <div class="summary-box">
                    <div class="summary-row">
                        <span class="muted">Sous-total</span>
                        <strong>{{ $formatMoney($quote->subtotal) }}</strong>
                    </div>
                    @if((float) $quote->discount_total > 0)
                        <div class="summary-row">
                            <span class="muted">Remise</span>
                            <strong>- {{ $formatMoney($quote->discount_total) }}</strong>
                        </div>
                    @endif
                    <div class="summary-row">
                        <span class="muted">Taxes</span>
                        <strong>{{ $formatMoney($quote->tax_total) }}</strong>
                    </div>
                    <div class="grand-total">
                        <span
                            style="text-transform: uppercase; letter-spacing: 0.14em; font-size: 12px; font-weight: 800;">
                            Total TTC
                        </span>
                        <strong>{{ $formatMoney($quote->total) }}</strong>
                    </div>
                </div>
            </section>

            @if($validUntil && in_array($quote->status, ['draft', 'sent']))
                <div class="validity-banner {{ $validityClass }}" style="margin-top: 28px;">
                    <span>
                        @if($validityClass === 'expired')
                            Ce devis a expiré le {{ $validUntil->translatedFormat('d F Y') }}.
                        @elseif($validityClass === 'expiring')
                            Ce devis expire bientôt — valide jusqu'au {{ $validUntil->translatedFormat('d F Y') }}.
                        @else
                            Ce devis est valable jusqu'au {{ $validUntil->translatedFormat('d F Y') }}.
                        @endif
                    </span>
                </div>
            @endif

            <div class="footer-note">
                {{ $notes ?: 'Merci de nous contacter pour toute question relative à ce devis.' }}<br>
                <br>
                © {{ now()->year }} {{ $companyName }}
                @if($company?->tax_number)
                    | NIF : {{ $company->tax_number }}
                @endif
            </div>
        </div>
    </div>

    @unless($isDownload ?? false)
        <div class="actions">
            <button class="button button-primary" onclick="window.print()">Imprimer le devis</button>
            <a href="{{ $downloadUrl }}" class="button button-secondary button-link">Télécharger le PDF</a>
        </div>
    @endunless
</body>

</html>