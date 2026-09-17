<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Relevé {{ $releve->numero }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #222; margin: 25px 30px; }
        .entete { display: flex; justify-content: space-between; border-bottom: 2px solid #087f6b; padding-bottom: 8px; }
        h1 { font-size: 18px; text-align: center; margin: 22px 0 4px; text-transform: uppercase; }
        .sous-titre { text-align: center; color: #666; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #bbb; padding: 5px 7px; }
        th { background: #087f6b; color: #fff; text-align: left; }
        .droite { text-align: right; }
        .total { font-weight: bold; background: #f2f2f2; }
        .signatures { display: flex; justify-content: space-between; margin-top: 50px; }
        @media print { .no-print { display: none; } body { margin: 12mm; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Imprimer</button>

    <div class="entete">
        <div>
            <strong>{{ $identite->nom }}</strong>
            <div>{{ $identite->coordonnees() }}</div>
        </div>
        <div style="text-align:right">
            <div>Relevé n° {{ $releve->numero }}</div>
            <div>Édité le {{ now()->format('d/m/Y') }}</div>
        </div>
    </div>

    <h1>Relevé d'analyses — clinique partenaire</h1>
    <div class="sous-titre">
        {{ $releve->partenariat->clinique?->nom }} · période du {{ $releve->periode_debut->format('d/m/Y') }} au {{ $releve->periode_fin->format('d/m/Y') }}
        @if($releve->echeance) · à régler avant le {{ $releve->echeance->format('d/m/Y') }}@endif
    </div>

    <table>
        <thead><tr><th>Demande</th><th>Date</th><th>Patient</th><th>Examens</th><th class="droite">Montant (GNF)</th></tr></thead>
        <tbody>
        @foreach($releve->creances as $creance)
            <tr>
                <td>{{ $creance->demande?->numero }}</td>
                <td>{{ $creance->demande?->created_at->format('d/m/Y') }}</td>
                <td>{{ $creance->demande?->patient?->full_name }}</td>
                <td>{{ $creance->demande?->examens->pluck('examen_nom')->join(', ') }}</td>
                <td class="droite">{{ number_format((float) $creance->montant, 0, ',', ' ') }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr class="total"><td colspan="4" class="droite">Total dû</td><td class="droite">{{ number_format((float) $releve->montant_total, 0, ',', ' ') }}</td></tr>
            @if($releve->montantRegle() > 0)
                <tr><td colspan="4" class="droite">Déjà réglé</td><td class="droite">{{ number_format($releve->montantRegle(), 0, ',', ' ') }}</td></tr>
                <tr class="total"><td colspan="4" class="droite">Reste dû</td><td class="droite">{{ number_format($releve->resteDu(), 0, ',', ' ') }}</td></tr>
            @endif
        </tfoot>
    </table>

    <div class="signatures">
        <div>Pour le laboratoire<br><br><br>_______________________</div>
        <div>Pour la clinique<br><br><br>_______________________</div>
    </div>
</body>
</html>
