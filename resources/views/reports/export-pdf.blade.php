<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <title>Rapport financier</title>
    <style>
        @page {
            margin: 14mm;
        }

        :root {
            --primary: #002045;
            --primary-soft: #1a365d;
            --surface: #f8f9ff;
            --surface-low: #eff4ff;
            --line: #d6e3ff;
            --text: #0b1c30;
            --muted: #57657a;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: var(--text);
            font-size: 12px;
            margin: 0;
            padding: 0;
            background: var(--surface);
        }

        .report-container {
            background: white;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 18px 20px;
        }

        h1 {
            margin: 0 0 6px;
            font-size: 22px;
            color: var(--primary);
            letter-spacing: 0.03em;
        }

        .muted {
            color: var(--muted);
        }

        .meta {
            margin-bottom: 8px;
            font-size: 12px;
        }

        .grid {
            width: 100%;
            margin: 18px 0;
            border-collapse: separate;
            border-spacing: 0;
        }

        .grid td {
            width: 50%;
            vertical-align: top;
            padding: 8px;
        }

        .card {
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface-low);
            padding: 10px 12px;
            min-height: 58px;
        }

        .card-label {
            color: var(--muted);
            font-size: 11px;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .card-value {
            color: var(--primary);
            font-size: 16px;
            font-weight: 700;
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
            background: var(--surface-low);
            color: var(--primary-soft);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .text-right {
            text-align: right;
        }

        .empty {
            text-align: center;
            color: var(--muted);
            padding: 16px 6px;
        }
    </style>
</head>

<body>
    @php
        $summaryChunks = array_chunk($summary, 2);
    @endphp

    <div class="report-container">
        <h1>Rapport financier automatisé</h1>
        <p class="meta muted">Généré le {{ $meta['generatedAt'] }} • Période {{ $meta['startDate'] }} →
            {{ $meta['endDate'] }}
        </p>

        <table class="grid">
            <tbody>
                @foreach ($summaryChunks as $chunk)
                    <tr>
                        @foreach ($chunk as $item)
                            <td>
                                <div class="card">
                                    <div class="card-label">{{ $item['label'] }}</div>
                                    <div class="card-value">{{ $item['value'] }}</div>
                                </div>
                            </td>
                        @endforeach
                        @if (count($chunk) === 1)
                            <td></td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Titre</th>
                    <th>Description</th>
                    <th class="text-right">Montant</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>{{ $row['date'] }}</td>
                        <td>{{ $row['title'] }}</td>
                        <td>{{ $row['subtitle'] }}</td>
                        <td class="text-right">{{ $row['amount'] }}</td>
                        <td>{{ $row['badge'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty">Aucune donnée disponible pour cette période.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>

</html>