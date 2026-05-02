<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $creditNote->credit_number }} - Avoir</title>
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
            --credit: #0369a1;
            --credit-soft: #0c4a6e;
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
            max-width: 1000px;
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
            background: var(--credit);
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
            opacity: 0.1;
            transform: translate(-50%, -50%) rotate(-28deg);
            border: 14px solid currentColor;
            border-radius: 36px;
            padding: 22px 40px;
            text-align: center;
            text-transform: uppercase;
        }

        .watermark-main {
            font-size: 100px;
            line-height: 0.95;
            font-weight: 900;
            letter-spacing: 0.18em;
            white-space: nowrap;
        }

        .watermark-sub {
            margin-top: 6px;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: 0.6em;
            white-space: nowrap;
        }

        .watermark-void {
            color: #74777f;
            border-style: dashed;
        }

        .watermark-pending {
            color: #d97706;
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
            color: var(--credit);
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

        .credit-amount-hero {
            background: linear-gradient(135deg, var(--credit-soft), var(--credit));
            border-radius: 20px;
            padding: 28px 36px;
            margin: 0 0 28px;
            color: white;
            text-align: center;
        }

        .credit-amount-label {
            font-size: 12px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            opacity: 0.85;
            margin-bottom: 10px;
        }

        .credit-amount-value {
            font-size: 52px;
            font-weight: 900;
            line-height: 1;
        }

        .credit-amount-note {
            margin-top: 10px;
            font-size: 13px;
            opacity: 0.8;
        }

        .reason-box {
            background: #e0f2fe;
            border: 1px solid #7dd3fc;
            border-radius: 14px;
            padding: 20px 24px;
            margin-bottom: 24px;
        }

        .invoice-link-box {
            background: var(--surface-low);
            border-radius: 14px;
            padding: 20px 24px;
            display: table;
            width: 100%;
            table-layout: fixed;
            margin-bottom: 24px;
        }

        .invoice-link-box>div {
            display: table-cell;
            vertical-align: middle;
        }

        .status-pill {
            display: inline-block;
            padding: 5px 16px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .status-issued {
            background: #dbeafe;
            color: #1e3a8a;
        }

        .status-pending-approval {
            background: #fef3c7;
            color: #78350f;
        }

        .status-approved {
            background: #dcfce7;
            color: #14532d;
        }

        .status-applied {
            background: #ede9fe;
            color: #4c1d95;
        }

        .status-void {
            background: #f1f5f9;
            color: #475569;
        }

        .footer-note {
            margin-top: 36px;
            padding-top: 20px;
            border-top: 1px solid var(--line);
            font-size: 12px;
            color: var(--muted);
            line-height: 1.7;
            text-align: right;
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
            max-width: 1000px;
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
        $client = $creditNote->invoice?->client;
        $clientName = $client?->company_name ?: $client?->contact_name ?: 'Client';
        $clientAddress = array_filter([$client?->address, $client?->city, $client?->country]);
        $downloadUrl = route('credit-notes.pdf', ['creditNote' => $creditNote, 'download' => 1]);

        $statusLabel = match ($creditNote->status) {
            'issued' => 'Émis',
            'pending_approval' => 'En attente d\'approbation',
            'approved' => 'Approuvé',
            'applied' => 'Appliqué',
            'void' => 'Annulé',
            default => ucfirst((string) $creditNote->status),
        };

        $statusCss = match ($creditNote->status) {
            'issued' => 'status-issued',
            'pending_approval' => 'status-pending-approval',
            'approved' => 'status-approved',
            'applied' => 'status-applied',
            'void' => 'status-void',
            default => 'status-issued',
        };

        $watermark = match ($creditNote->status) {
            'void' => ['main' => 'ANNULÉ', 'sub' => 'VOID', 'class' => 'watermark-void'],
            'pending_approval' => ['main' => 'EN ATTENTE', 'sub' => 'PENDING', 'class' => 'watermark-pending'],
            default => null,
        };
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
                        @if($company?->email)
                            <div style="margin-top: 8px;"><strong>E-mail :</strong> {{ $company->email }}</div>
                        @endif
                    </div>
                </div>

                <div style="text-align: right;">
                    <p class="doc-word">Avoir</p>
                    <div class="doc-number-label">Numéro d'avoir</div>
                    <div class="doc-number-value">{{ $creditNote->credit_number }}</div>
                </div>
            </header>

            <section class="meta-grid">
                <div class="panel">
                    <div class="section-label" style="margin-bottom: 12px;">Client</div>
                    <div style="font-size: 22px; font-weight: 800; color: var(--text);">{{ $clientName }}</div>
                    <div class="muted" style="font-size: 14px; line-height: 1.7; margin-top: 10px;">
                        @forelse($clientAddress as $line)
                            <div>{{ $line }}</div>
                        @empty
                            <div>Adresse non renseignée</div>
                        @endforelse
                        @if($client?->email)
                            <div style="margin-top: 8px;"><strong>E-mail :</strong> {{ $client->email }}</div>
                        @endif
                        @if($client?->phone)
                            <div><strong>Téléphone :</strong> {{ $client->phone }}</div>
                        @endif
                    </div>
                </div>

                <div>
                    <div class="meta-line">
                        <span class="section-label">Date d'émission</span>
                        <strong>{{ optional($creditNote->issue_date)?->translatedFormat('d F Y') ?: '—' }}</strong>
                    </div>
                    <div class="meta-line">
                        <span class="section-label">Statut</span>
                        <span class="status-pill {{ $statusCss }}">{{ $statusLabel }}</span>
                    </div>
                    @if($creditNote->invoice)
                        <div class="meta-line">
                            <span class="section-label">Facture d'origine</span>
                            <strong>{{ $creditNote->invoice->invoice_number }}</strong>
                        </div>
                    @endif
                </div>
            </section>

            <div class="credit-amount-hero">
                <div class="credit-amount-label">Montant de l'avoir</div>
                <div class="credit-amount-value">{{ $formatMoney($creditNote->amount) }}</div>
                <div class="credit-amount-note">
                    Ce crédit sera déduit du solde dû par {{ $clientName }}.
                </div>
            </div>

            <div class="reason-box">
                <div class="section-label" style="margin-bottom: 10px; color: var(--credit);">Motif de l'avoir</div>
                <p style="margin: 0; font-size: 15px; line-height: 1.7; color: var(--credit-soft);">
                    {{ $creditNote->reason }}
                </p>
            </div>

            @if($creditNote->invoice)
                <div class="invoice-link-box">
                    <div>
                        <div class="section-label" style="display: block; margin-bottom: 8px;">Facture liée</div>
                        <div style="font-size: 20px; font-weight: 800; color: var(--primary);">
                            {{ $creditNote->invoice->invoice_number }}
                        </div>
                        @if($creditNote->invoice->issue_date)
                            <div class="muted" style="font-size: 13px; margin-top: 4px;">
                                Émise le {{ $creditNote->invoice->issue_date->translatedFormat('d F Y') }}
                            </div>
                        @endif
                    </div>
                    <div style="text-align: right;">
                        <div class="section-label" style="display: block; margin-bottom: 8px;">Total facture</div>
                        <div style="font-size: 20px; font-weight: 800; color: var(--primary);">
                            {{ $formatMoney($creditNote->invoice->total) }}
                        </div>
                    </div>
                </div>
            @endif

            <div class="footer-note">
                Cet avoir a été émis par {{ $companyName }} en déduction de la facture associée.<br>
                Veuillez conserver ce document pour vos archives.
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
            <button class="button button-primary" onclick="window.print()">Imprimer l'avoir</button>
            <a href="{{ $downloadUrl }}" class="button button-secondary button-link">Télécharger le PDF</a>
        </div>
    @endunless
</body>

</html>