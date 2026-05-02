<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Note de frais - {{ $expense->title }}</title>
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
            --accent: #7c3aed;
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
            background: var(--accent);
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

        .watermark-approved {
            color: #0f766e;
        }

        .watermark-rejected {
            color: var(--danger);
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
            font-size: 40px;
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

        .expense-title-block {
            margin-bottom: 32px;
        }

        .expense-main-title {
            font-size: 28px;
            font-weight: 800;
            color: var(--primary);
            line-height: 1.2;
            margin: 0 0 8px;
        }

        .category-pill {
            display: inline-block;
            padding: 5px 16px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            background: #ede9fe;
            color: #5b21b6;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .amount-hero {
            background: linear-gradient(135deg, #5b21b6, var(--accent));
            border-radius: 20px;
            padding: 28px 32px;
            margin: 0 0 28px;
            color: white;
            display: table;
            width: 100%;
            table-layout: fixed;
        }

        .amount-hero>div {
            display: table-cell;
            vertical-align: middle;
        }

        .amount-label {
            font-size: 12px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            opacity: 0.85;
            margin-bottom: 8px;
        }

        .amount-value {
            font-size: 48px;
            font-weight: 900;
            line-height: 1;
        }

        .details-grid {
            display: table;
            width: 100%;
            table-layout: fixed;
            margin-bottom: 24px;
        }

        .details-grid>div {
            display: table-cell;
            vertical-align: top;
            padding-right: 24px;
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
            display: flex;
            justify-content: space-between;
            gap: 8px;
            padding: 8px 0;
            border-bottom: 1px solid rgba(87, 101, 122, 0.15);
            font-size: 14px;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-row .label {
            color: var(--muted);
        }

        .detail-row .value {
            font-weight: 700;
            color: var(--primary);
            text-align: right;
        }

        .approval-box {
            border-radius: 14px;
            padding: 20px 24px;
            margin-top: 24px;
        }

        .approval-approved {
            background: #dcfce7;
            border: 1px solid #86efac;
            color: #14532d;
        }

        .approval-rejected {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #7f1d1d;
        }

        .approval-pending {
            background: #fef3c7;
            border: 1px solid #fcd34d;
            color: #78350f;
        }

        .approval-review {
            background: #ede9fe;
            border: 1px solid #c4b5fd;
            color: #4c1d95;
        }

        .approval-title {
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .approval-detail {
            font-size: 14px;
            line-height: 1.6;
        }

        .description-box {
            background: var(--surface-low);
            border-radius: 14px;
            padding: 20px 24px;
            margin-top: 24px;
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
        $downloadUrl = route('expenses.pdf', ['expense' => $expense, 'download' => 1]);

        $categoryLabel = match ($expense->category) {
            'travel' => 'Déplacement',
            'supplies' => 'Fournitures',
            'operations' => 'Opérations',
            'payroll' => 'Paie',
            'compliance' => 'Conformité',
            'other' => 'Autre',
            default => ucfirst(str_replace('_', ' ', (string) $expense->category)),
        };

        $approvalLabel = match ($expense->approval_status) {
            'approved' => ['text' => 'Approuvée', 'css' => 'approval-approved'],
            'rejected' => ['text' => 'Refusée', 'css' => 'approval-rejected'],
            'pending_approval' => ['text' => 'En attente', 'css' => 'approval-pending'],
            'review' => ['text' => 'En cours de revue', 'css' => 'approval-review'],
            default => ['text' => ucfirst((string) $expense->approval_status), 'css' => 'approval-pending'],
        };

        $watermark = match ($expense->approval_status) {
            'approved' => ['main' => 'APPROUVÉE', 'sub' => 'APPROVED', 'class' => 'watermark-approved'],
            'rejected' => ['main' => 'REFUSÉE', 'sub' => 'REJECTED', 'class' => 'watermark-rejected'],
            default => null,
        };

        $methodLabel = match (strtolower((string) $expense->payment_method)) {
            'cash' => 'Espèces',
            'bank_transfer' => 'Virement bancaire',
            'check' => 'Chèque',
            'mobile_money' => 'Mobile Money',
            'card' => 'Carte bancaire',
            default => ucfirst(str_replace('_', ' ', (string) $expense->payment_method)),
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
                    </div>
                </div>

                <div style="text-align: right;">
                    <p class="doc-word">Note de frais</p>
                    @if($expense->reference)
                        <div class="doc-number-label">Référence</div>
                        <div style="margin-top: 8px; font-size: 22px; font-weight: 800; color: var(--primary);">
                            {{ $expense->reference }}
                        </div>
                    @endif
                </div>
            </header>

            <div class="expense-title-block">
                <div class="expense-main-title">{{ $expense->title }}</div>
                <span class="category-pill">{{ $categoryLabel }}</span>
            </div>

            <div class="amount-hero">
                <div>
                    <div class="amount-label">Montant de la dépense</div>
                    <div class="amount-value">{{ $formatMoney($expense->amount) }}</div>
                </div>
                <div style="text-align: right;">
                    <div
                        style="font-size: 12px; letter-spacing: 0.14em; text-transform: uppercase; opacity: 0.75; margin-bottom: 8px;">
                        Date
                    </div>
                    <div style="font-size: 22px; font-weight: 800;">
                        {{ optional($expense->expense_date)?->translatedFormat('d F Y') ?: '—' }}
                    </div>
                </div>
            </div>

            <div class="details-grid">
                <div>
                    <div class="detail-card">
                        <span class="section-label">Informations</span>
                        @if($expense->vendor)
                            <div class="detail-row">
                                <span class="label">Fournisseur</span>
                                <span class="value">{{ $expense->vendor }}</span>
                            </div>
                        @endif
                        @if($expense->payment_method)
                            <div class="detail-row">
                                <span class="label">Mode de paiement</span>
                                <span class="value">{{ $methodLabel }}</span>
                            </div>
                        @endif
                        @if($expense->reference)
                            <div class="detail-row">
                                <span class="label">Référence</span>
                                <span class="value">{{ $expense->reference }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <div>
                    <div class="detail-card">
                        <span class="section-label">Enregistrement</span>
                        @if($expense->recorder)
                            <div class="detail-row">
                                <span class="label">Enregistré par</span>
                                <span class="value">{{ $expense->recorder->name }}</span>
                            </div>
                        @endif
                        <div class="detail-row">
                            <span class="label">Date d'enregistrement</span>
                            <span class="value">{{ $expense->created_at?->translatedFormat('d F Y') ?: '—' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            @if($expense->description)
                <div class="description-box">
                    <div class="section-label" style="margin-bottom: 10px;">Description</div>
                    <p style="margin: 0; font-size: 14px; line-height: 1.7;">{{ $expense->description }}</p>
                </div>
            @endif

            <div class="approval-box {{ $approvalLabel['css'] }}">
                <div class="approval-title">Statut d'approbation : {{ $approvalLabel['text'] }}</div>
                @if($expense->approver)
                    <div class="approval-detail">
                        {{ $expense->approver->name }}
                        @if($expense->approved_at)
                            — {{ $expense->approved_at->translatedFormat('d F Y à H:i') }}
                        @endif
                    </div>
                @endif
                @if($expense->approval_notes)
                    <div class="approval-detail" style="margin-top: 6px;">
                        <strong>Notes :</strong> {{ $expense->approval_notes }}
                    </div>
                @endif
            </div>

            <div class="footer-note">
                Ce document est une note de frais interne générée par {{ $companyName }}.
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
            <button class="button button-primary" onclick="window.print()">Imprimer</button>
            <a href="{{ $downloadUrl }}" class="button button-secondary button-link">Télécharger le PDF</a>
        </div>
    @endunless
</body>

</html>