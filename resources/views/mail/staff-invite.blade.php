<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation collaborateur</title>
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

        .credentials {
            background: #eff4ff;
            border-radius: 12px;
            padding: 20px 24px;
            margin: 24px 0;
        }

        .credentials label {
            display: block;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: #57657a;
            margin-bottom: 4px;
            margin-top: 16px;
        }

        .credentials label:first-child {
            margin-top: 0;
        }

        .credentials span {
            font-size: 15px;
            font-weight: 700;
            color: #002045;
        }

        .credentials code {
            font-family: 'Courier New', monospace;
            font-size: 15px;
            font-weight: 700;
            color: #002045;
            background: #dde6ff;
            padding: 2px 8px;
            border-radius: 6px;
        }

        .cta {
            display: inline-block;
            background: #002045;
            color: #ffffff;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            margin: 4px 0 24px;
        }

        .warning {
            background: #fff8e1;
            border-left: 4px solid #f59e0b;
            border-radius: 8px;
            padding: 14px 20px;
            margin-top: 24px;
            font-size: 13px;
            color: #78350f;
            line-height: 1.6;
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
            <h1>{{ e($companyName) }}</h1>
            <p>Invitation collaborateur · {{ now()->format('d/m/Y') }}</p>
        </div>
        <div class="body">
            <p style="font-size:15px; margin-bottom:16px;">
                Bonjour <strong>{{ e($user->name) }}</strong>,
            </p>
            <p style="font-size:14px; color:#374151; line-height:1.7; margin-bottom:24px;">
                Vous avez été invité(e) à rejoindre la plateforme ERP de <strong>{{ e($companyName) }}</strong>
                en tant que <strong>{{ e($roleLabel) }}</strong>.
                Voici vos identifiants de connexion provisoires :
            </p>

            <div class="credentials">
                <label>Adresse e-mail</label>
                <span>{{ e($user->email) }}</span>

                <label>Mot de passe provisoire</label>
                <code>{{ e($temporaryPassword) }}</code>
            </div>

            <a href="{{ $loginUrl }}" class="cta">Accéder à la plateforme</a>

            <div class="warning">
                ⚠ Pour des raisons de sécurité, veuillez modifier votre mot de passe dès votre première connexion.
                Ce mot de passe provisoire est à usage unique.
            </div>

            <p style="font-size:13px; color:#57657a; margin-top:24px; line-height:1.7;">
                Pour toute question, contactez votre administrateur à
                <a href="mailto:{{ e($companyEmail) }}" style="color:#002045;">{{ e($companyEmail) }}</a>.
            </p>
        </div>
        <div class="footer">
            <p>
                Ce message est généré automatiquement par le système ERP de <strong>{{ e($companyName) }}</strong>.<br>
                Si vous n'êtes pas le destinataire prévu, merci d'ignorer cet e-mail.
            </p>
        </div>
    </div>
</body>

</html>