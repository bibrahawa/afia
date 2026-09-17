<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Bordereau {{ $bordereau->numero }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #222; margin: 20px; }
        .entete { display: flex; justify-content: space-between; border-bottom: 2px solid #333; padding-bottom: 8px; margin-bottom: 12px; }
        .entete img { max-height: 60px; }
        h1 { font-size: 16px; margin: 0 0 6px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 4px; vertical-align: top; }
        th { background: #eee; }
        .text-end { text-align: right; } .text-muted { color: #666; } .fw-bold { font-weight: bold; }
        .signatures { display: flex; justify-content: space-between; margin-top: 40px; }
        @media print { .no-print { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Imprimer</button>

    <div class="entete">
        <div>
            @if($identite->logoWeb())<img src="{{ $identite->logoWeb() }}" alt="Logo">@endif
            <div class="fw-bold">{{ $identite->nom }}</div>
            <div>{{ $identite->coordonnees() }}</div>
        </div>
        <div class="text-end">
            <h1>Bordereau de réclamations</h1>
            <div>N° <strong>{{ $bordereau->numero }}</strong></div>
            <div>Organisme : <strong>{{ $bordereau->organisme->name }}</strong></div>
            <div>Période : {{ $bordereau->periode_debut?->format('d/m/Y') ?? '…' }} → {{ $bordereau->periode_fin?->format('d/m/Y') ?? '…' }}</div>
            <div>Date d'envoi : {{ $bordereau->date_envoi?->format('d/m/Y') ?? today()->format('d/m/Y') }}</div>
        </div>
    </div>

    @include('assurance.bordereaux._tableau', ['impression' => true])

    <div class="signatures">
        <div>Pour l'établissement<br><br><br>Signature et cachet</div>
        <div>Reçu par l'organisme le : ____/____/________<br><br><br>Signature et cachet</div>
    </div>
</body>
</html>
