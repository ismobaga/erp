<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portail client – {{ $client->company_name ?: $client->contact_name }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f7fb; color: #002045; min-height: 100vh; }
        .topbar { background: linear-gradient(135deg, #002045 0%, #1a365d 100%); padding: 20px 32px; display: flex; align-items: center; justify-content: space-between; }
        .topbar-brand { color: #fff; font-size: 18px; font-weight: 800; letter-spacing: -0.02em; }
        .topbar-sub { color: rgba(255,255,255,0.6); font-size: 12px; margin-top: 2px; }
        .container { max-width: 860px; margin: 0 auto; padding: 32px 16px; }
        .hero { background: #fff; border-radius: 16px; padding: 32px; margin-bottom: 28px; box-shadow: 0 2px 12px rgba(0,32,69,0.07); ring: 1px solid #e5eaf2; }
        .hero h1 { font-size: 26px; font-weight: 900; color: #002045; margin-bottom: 4px; }
        .hero p { font-size: 14px; color: #57657a; }
        .badge { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 10px; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; }
        .badge-draft { background: #f1f5f9; color: #57657a; }
        .badge-sent { background: #eff6ff; color: #1d4ed8; }
        .badge-paid { background: #dcfce7; color: #166534; }
        .badge-overdue { background: #fee2e2; color: #991b1b; }
        .badge-partially_paid { background: #e0f2fe; color: #0369a1; }
        .badge-cancelled { background: #f1f5f9; color: #6b7280; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,32,69,0.07); }
        thead tr { background: #f8faff; }
        th { padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.12em; color: #57657a; }
        td { padding: 14px 16px; font-size: 14px; border-top: 1px solid #f0f4f9; }
        tr:hover td { background: #f8faff; }
        .amount { font-weight: 700; font-variant-numeric: tabular-nums; }
        .balance-due { color: #ba1a1a; font-weight: 700; }
        .balance-zero { color: #166534; }
        a.btn { display: inline-block; padding: 6px 16px; background: #002045; color: #fff; border-radius: 8px; text-decoration: none; font-size: 12px; font-weight: 700; }
        a.btn:hover { opacity: 0.88; }
        .empty { text-align: center; padding: 60px 20px; color: #57657a; }
        .empty-icon { font-size: 48px; margin-bottom: 12px; }
        footer { text-align: center; padding: 32px 16px; font-size: 12px; color: #aab0bc; }
    </style>
</head>
<body>
    <div class="topbar">
        <div>
            <div class="topbar-brand">{{ $company?->company_name ?: config('app.name') }}</div>
            <div class="topbar-sub">Portail client sécurisé</div>
        </div>
    </div>

    <div class="container">
        <div class="hero">
            <h1>{{ $client->company_name ?: $client->contact_name }}</h1>
            <p>Bienvenue sur votre portail. Consultez et téléchargez vos factures en toute sécurité.</p>
        </div>

        @if($invoices->isEmpty())
            <div class="empty">
                <div class="empty-icon">📄</div>
                <p style="font-size:16px;font-weight:700;margin-bottom:8px;">Aucune facture pour le moment</p>
                <p>Vos factures apparaîtront ici dès qu'elles auront été émises.</p>
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Facture</th>
                        <th>Date d'émission</th>
                        <th>Échéance</th>
                        <th>Montant</th>
                        <th>Solde dû</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $invoice)
                    <tr>
                        <td style="font-weight:700;">{{ $invoice->invoice_number }}</td>
                        <td>{{ $invoice->issue_date?->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ $invoice->due_date?->format('d/m/Y') ?? '—' }}</td>
                        <td class="amount">FCFA {{ number_format((float) $invoice->total, 0, '.', ' ') }}</td>
                        <td class="amount {{ (float) $invoice->balance_due > 0 ? 'balance-due' : 'balance-zero' }}">
                            FCFA {{ number_format((float) $invoice->balance_due, 0, '.', ' ') }}
                        </td>
                        <td>
                            <span class="badge badge-{{ $invoice->status }}">
                                {{ match($invoice->status) {
                                    'draft'          => 'Brouillon',
                                    'sent'           => 'Envoyée',
                                    'paid'           => 'Payée',
                                    'overdue'        => 'En retard',
                                    'partially_paid' => 'Part. payée',
                                    'cancelled'      => 'Annulée',
                                    default          => $invoice->status,
                                } }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('portal.invoice', ['token' => $token, 'invoice' => $invoice]) }}" class="btn">Voir</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <footer>
        <p>{{ $company?->company_name ?: config('app.name') }} · Portail client sécurisé · Ne partagez pas ce lien.</p>
    </footer>
</body>
</html>
