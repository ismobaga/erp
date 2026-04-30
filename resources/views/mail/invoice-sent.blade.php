<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f7fb; margin: 0; padding: 32px 0; color: #002045; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,32,69,0.08); }
        .header { background: linear-gradient(135deg, #002045 0%, #1a365d 100%); padding: 36px 40px; }
        .header h1 { color: #ffffff; font-size: 22px; margin: 0 0 4px; }
        .header p { color: rgba(255,255,255,0.7); font-size: 13px; margin: 0; }
        .body { padding: 36px 40px; }
        .meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 28px; }
        .meta-item label { display: block; font-size: 10px; font-weight: 800; letter-spacing: 0.18em; text-transform: uppercase; color: #57657a; margin-bottom: 4px; }
        .meta-item span { font-size: 16px; font-weight: 800; color: #002045; }
        .amount { font-size: 28px !important; color: #002045 !important; }
        .cta { display: block; text-align: center; background: #002045; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 10px; font-size: 13px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; margin: 28px 0; }
        .footer { padding: 20px 40px; background: #f4f7fb; border-top: 1px solid #e5eaf2; }
        .footer p { font-size: 12px; color: #74777f; margin: 0; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $companyName }}</h1>
            <p>Facture · {{ now()->format('d/m/Y') }}</p>
        </div>
        <div class="body">
            <p style="font-size:15px; margin-bottom:24px;">
                Bonjour {{ $invoice->client?->contact_name ?? $invoice->client?->company_name ?? 'Client' }},
            </p>
            <p style="font-size:14px; color:#374151; margin-bottom:24px; line-height:1.7;">
                Veuillez trouver ci-joint votre facture <strong>{{ $invoice->invoice_number }}</strong>.
                Nous vous remercions de votre confiance et restons disponibles pour toute question.
            </p>

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
                    <label>Montant total</label>
                    <span class="amount">{{ $formattedTotal }}</span>
                </div>
                <div class="meta-item">
                    <label>Statut</label>
                    <span style="color:#1d4ed8;">Envoyée</span>
                </div>
            </div>

            @if($portalUrl)
            <a href="{{ $portalUrl }}" class="cta">Consulter votre facture en ligne</a>
            @endif

            <p style="font-size:14px;color:#374151;line-height:1.7;margin-bottom:8px;">
                Pour toute question, contactez-nous à
                <a href="mailto:{{ $companyEmail }}" style="color:#002045;">{{ $companyEmail }}</a>.
            </p>
        </div>
        <div class="footer">
            <p>
                Ce message est généré automatiquement par le système ERP de <strong>{{ $companyName }}</strong>.<br>
                La facture en PDF est jointe à cet e-mail.
            </p>
        </div>
    </div>
</body>
</html>
