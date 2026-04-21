<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rappel de paiement</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f4f7fb;
            margin: 0;
            padding: 32px 0;
            color: #002045;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0, 32, 69, 0.08);
        }

        .header {
            background: linear-gradient(135deg, #002045 0%, #1a365d 100%);
            padding: 36px 40px;
        }

        .header h1 {
            color: #ffffff;
            font-size: 22px;
            margin: 0 0 4px;
        }

        .header p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 13px;
            margin: 0;
        }

        .body {
            padding: 36px 40px;
        }

        .alert-box {
            background: #fff4f2;
            border-left: 4px solid #ba1a1a;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 28px;
        }

        .alert-box p {
            margin: 0;
            font-size: 14px;
            color: #ba1a1a;
            font-weight: 600;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 28px;
        }

        .meta-item label {
            display: block;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: #57657a;
            margin-bottom: 4px;
        }

        .meta-item span {
            font-size: 16px;
            font-weight: 800;
            color: #002045;
        }

        .amount {
            font-size: 28px !important;
            color: #ba1a1a !important;
        }

        .cta {
            display: block;
            text-align: center;
            background: #002045;
            color: #ffffff;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            margin: 28px 0;
        }

        .footer {
            padding: 20px 40px;
            background: #f4f7fb;
            border-top: 1px solid #e5eaf2;
        }

        .footer p {
            font-size: 12px;
            color: #74777f;
            margin: 0;
            line-height: 1.6;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>{{ $companyName }}</h1>
            <p>Rappel de paiement · {{ now()->format('d/m/Y') }}</p>
        </div>
        <div class="body">
            <p style="font-size:15px; margin-bottom:24px;">
                Bonjour {{ $invoice->client?->contact_name ?? $invoice->client?->company_name ?? 'Client' }},
            </p>
            <p style="font-size:14px; color:#374151; margin-bottom:24px; line-height:1.7;">
                Nous vous contactons au sujet de la facture ci-dessous qui demeure en attente de règlement.
                Nous vous saurions gré d'effectuer le paiement dans les meilleurs délais afin d'éviter toute pénalité de
                retard.
            </p>

            <div class="alert-box">
                <p>⚠ Cette facture est en retard de paiement.</p>
            </div>

            <div class="meta-grid">
                <div class="meta-item">
                    <label>Numéro de facture</label>
                    <span>{{ $invoice->invoice_number }}</span>
                </div>
                <div class="meta-item">
                    <label>Date d'échéance</label>
                    <span>{{ $formattedDueDate }}</span>
                </div>
                <div class="meta-item">
                    <label>Montant dû</label>
                    <span class="amount">{{ $formattedAmount }}</span>
                </div>
                <div class="meta-item">
                    <label>Statut</label>
                    <span style="color:#ba1a1a;">En retard</span>
                </div>
            </div>

            @if($invoice->notes)
                <p style="font-size:13px;color:#57657a;margin-bottom:24px;">
                    <strong>Note :</strong> {{ $invoice->notes }}
                </p>
            @endif

            <p style="font-size:14px;color:#374151;line-height:1.7;margin-bottom:8px;">
                Pour toute question concernant cette facture, n'hésitez pas à nous contacter directement à
                <a href="mailto:{{ $companyEmail }}" style="color:#002045;">{{ $companyEmail }}</a>.
            </p>
        </div>
        <div class="footer">
            <p>
                Ce message est généré automatiquement par le système ERP de <strong>{{ $companyName }}</strong>.<br>
                Merci de ne pas répondre directement à cet e-mail.
            </p>
        </div>
    </div>
</body>

</html>