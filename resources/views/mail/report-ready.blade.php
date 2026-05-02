<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport programmé disponible</title>
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

        .meta-item {
            margin-bottom: 16px;
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
            font-size: 15px;
            font-weight: 700;
            color: #002045;
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
            <p>Rapport programmé · {{ now()->format('d/m/Y') }}</p>
        </div>
        <div class="body">
            <p style="font-size:15px; margin-bottom:24px;">Bonjour,</p>
            <p style="font-size:14px; color:#374151; margin-bottom:24px; line-height:1.7;">
                Votre rapport programmé a été généré avec succès et est joint à cet e-mail.
            </p>
            <div class="meta-item">
                <label>Généré le</label>
                <span>{{ $generatedAt }}</span>
            </div>
            <div class="meta-item">
                <label>Fichier</label>
                <span>{{ basename($reportPath) }}</span>
            </div>
        </div>
        <div class="footer">
            <p>Cet e-mail a été envoyé automatiquement par {{ $companyName }}. Veuillez ne pas y répondre.</p>
        </div>
    </div>
</body>

</html>