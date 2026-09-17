<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $document->type->libelle() }} — {{ $document->numero }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; color: #222; margin: 30px 40px; }
        .entete { display: flex; justify-content: space-between; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .entete img { max-height: 70px; }
        h1 { font-size: 20px; text-align: center; text-transform: uppercase; letter-spacing: 1px; margin: 30px 0 6px; }
        .numero { text-align: center; color: #666; margin-bottom: 30px; }
        .contenu { line-height: 1.8; white-space: pre-wrap; min-height: 220px; }
        .annule { color: #c0262d; font-weight: bold; text-align: center; margin: 10px 0; }
        .signature { margin-top: 50px; text-align: right; }
        .pied { margin-top: 40px; border-top: 1px solid #999; padding-top: 6px; font-size: 11px; color: #666; }
        @media print { .no-print { display: none; } body { margin: 15mm; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Imprimer</button>

    <div class="entete">
        <div>
            @if($identite->logoWeb())<img src="{{ $identite->logoWeb() }}" alt="Logo">@endif
            <div><strong>{{ $identite->nom }}</strong></div>
            <div>{{ $identite->coordonnees() }}</div>
        </div>
        <div style="text-align:right">
            <div>{{ $document->created_at->format('d/m/Y') }}</div>
            @if($document->medecin)<div>Dr {{ $document->medecin->full_name }}</div>@endif
        </div>
    </div>

    <h1>{{ $document->type->libelle() }}</h1>
    <div class="numero">N° {{ $document->numero }}</div>

    @if($document->annule)
        <div class="annule">DOCUMENT ANNULÉ — {{ $document->motif_annulation }}</div>
    @endif

    <div class="contenu">{{ $document->contenu }}</div>

    @if($document->date_debut && $document->date_fin)
        <p><strong>Période :</strong> du {{ $document->date_debut->format('d/m/Y') }} au {{ $document->date_fin->format('d/m/Y') }} inclus
            ({{ $document->jours }} jour(s)).</p>
    @endif

    <div class="signature">
        Fait à {{ $identite->ville ?? 'Conakry' }}, le {{ $document->created_at->format('d/m/Y') }}<br><br><br>
        Signature et cachet du médecin
    </div>

    <div class="pied">
        Patient : {{ $document->patient->full_name }}@if($document->patient->age !== null), {{ $document->patient->age }} ans @endif ·
        Document n° {{ $document->numero }} conservé au dossier de {{ $identite->nom }}.
    </div>
</body>
</html>
