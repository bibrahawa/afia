<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport de la Clinique</title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }

        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            font-size: 12px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 18px;
            margin: 0 0 5px 0;
        }

        .header p {
            margin: 0;
            font-size: 14px;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .report-table th,
        .report-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        .report-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .empty-cell {
            color: #999;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $clinique_nom ?? 'Clinique Médicale' }}</h1>
        <p>Rapport d'Activité - Période du {{ $date_debut ?? '10/07/2025' }} au {{ $date_fin ?? '14/07/2025' }}</p>
    </div>

    <table class="report-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Patient</th>
                <th>Actes</th>
                <th>Débit</th>
                <th>Crédit</th>
                <th>Solde</th>
            </tr>
        </thead>
        <tbody>
            @php
                $donnees = $donnees ?? [
                    ['date' => '10/07/2025', 'patient' => 'Oury Bah', 'actes' => 'Consultation', 'debit' => 250000, 'credit' => 200000, 'solde' => 50000],
                    ['date' => '11/07/2025', 'patient' => 'Ibrahim Barry', 'actes' => 'Échographie', 'debit' => 200000, 'credit' => 200000, 'solde' => 0],
                    ['date' => '12/07/2025', 'patient' => '', 'actes' => '', 'debit' => 0, 'credit' => 0, 'solde' => 0],
                    ['date' => '13/07/2025', 'patient' => '', 'actes' => '', 'debit' => 0, 'credit' => 0, 'solde' => 0],
                    ['date' => '14/07/2025', 'patient' => '', 'actes' => '', 'debit' => 0, 'credit' => 0, 'solde' => 0]
                ];
            @endphp

            @foreach($donnees as $ligne)
                <tr>
                    <td class="text-center">{{ $ligne['date'] }}</td>
                    <td>{{ $ligne['patient'] ?: '' }}</td>
                    <td>{{ $ligne['actes'] ?: '' }}</td>
                    <td class="text-right">
                        {{ $ligne['debit'] > 0 ? number_format($ligne['debit'], 0, ',', ' ') : '' }}
                    </td>
                    <td class="text-right">
                        {{ $ligne['credit'] > 0 ? number_format($ligne['credit'], 0, ',', ' ') : '' }}
                    </td>
                    <td class="text-right">
                        {{ $ligne['solde'] > 0 ? number_format($ligne['solde'], 0, ',', ' ') : '' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 20px; font-size: 11px;">
        <p><strong>Total Débit:</strong> {{ number_format(array_sum(array_column($donnees ?? [], 'debit')), 0, ',', ' ') }} GNF</p>
        <p><strong>Total Crédit:</strong> {{ number_format(array_sum(array_column($donnees ?? [], 'credit')), 0, ',', ' ') }} GNF</p>
        <p><strong>Solde Net:</strong> {{ number_format(array_sum(array_column($donnees ?? [], 'solde')), 0, ',', ' ') }} GNF</p>
    </div>

    <div style="margin-top: 30px; font-size: 10px; text-align: center; color: #666;">
        Généré le {{ date('d/m/Y à H:i') }}
    </div>
</body>
</html>