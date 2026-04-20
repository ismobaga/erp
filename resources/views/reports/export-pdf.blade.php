<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <title>Rapport financier</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #0b1c30;
            font-size: 12px;
        }

        h1 {
            margin-bottom: 4px;
            font-size: 22px;
        }

        .muted {
            color: #57657a;
        }

        .grid {
            width: 100%;
            margin: 18px 0;
        }

        .grid td {
            width: 50%;
            vertical-align: top;
            padding: 8px;
        }

        .card {
            border: 1px solid #d6e3ff;
            border-radius: 8px;
            padding: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }

        th,
        td {
            border-bottom: 1px solid #e5e7eb;
            padding: 8px 6px;
            text-align: left;
        }

        th {
            background: #eff4ff;
            font-size: 11px;
            text-transform: uppercase;
        }
    </style>
</head>

<body>
    <h1>Rapport financier automatisé</h1>
    <p class="muted">Généré le {{ $meta['generatedAt'] }} • Période {{ $meta['startDate'] }} → {{ $meta['endDate'] }}
    </p>

    <table class="grid">
        <tr>
            @foreach ($summary as $item)
                <td>
                    <div class="card">
                        <div class="muted">{{ $item['label'] }}</div>
                        <strong>{{ $item['value'] }}</strong>
                    </div>
                </td>
                @if (($loop->iteration % 2) === 0)
                    </tr>
                    <tr>
                @endif
            @endforeach
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Titre</th>
                <th>Description</th>
                <th>Montant</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['title'] }}</td>
                    <td>{{ $row['subtitle'] }}</td>
                    <td>{{ $row['amount'] }}</td>
                    <td>{{ $row['badge'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Aucune donnée disponible pour cette période.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>