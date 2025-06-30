<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 14px; }
        h1 { text-align: center; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>Facture Hospitalisation</h1>

    <p><strong>Numéro de facture :</strong> {{ $factureNo }}</p>
    <p><strong>Date :</strong> {{ now()->format('d/m/Y') }}</p>

    <hr>

    <p><strong>Patiente :</strong> {{ $hospitalisation->patient->first_name." ".$hospitalisation->patient->last_name }}</p>
    <p><strong>Chambre :</strong> {{ $hospitalisation->chambre->numero }} ({{ $hospitalisation->chambre->type }})</p>
    <p><strong>Date d’entrée :</strong> {{ $hospitalisation->date_entree }}</p>
    <p><strong>Date de sortie :</strong> {{ $hospitalisation->date_sortie_effective ?? now()->format('Y-m-d') }}</p>

    <table class="table">
        <thead>
            <tr>
                <th>Description</th>
                <th class="right">Prix unitaire</th>
                <th class="right">Quantité</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Hospitalisation ({{ number_format(ceil($nombreJours)) }} jours)</td>

                <td class="right">{{ number_format($hospitalisation->chambre->prix_par_jour) }} GNF</td>
                <td class="right">{{ number_format(ceil($nombreJours)) }}</td>
                <td class="right">{{ number_format($total) }} GNF</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="right">Total à payer</th>
                <th class="right">{{ number_format($total, 2) }} GNF</th>
            </tr>
        </tfoot>
    </table>

    <p style="margin-top: 40px;">Merci pour votre confiance.</p>
</body>
</html>
