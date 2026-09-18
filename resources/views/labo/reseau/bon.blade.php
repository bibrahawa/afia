<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Bon d'analyses {{ $demande->numero }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; color: #222; margin: 25px 30px; }
        .entete { display: flex; justify-content: space-between; border-bottom: 2px solid #087f6b; padding-bottom: 8px; }
        h1 { font-size: 18px; text-align: center; margin: 20px 0 4px; text-transform: uppercase; }
        .sous-titre { text-align: center; color: #666; margin-bottom: 18px; }
        .bloc { border: 1px solid #ccc; border-radius: 6px; padding: 10px 12px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #bbb; padding: 5px 7px; text-align: left; }
        th { background: #087f6b; color: #fff; }
        .consignes { background: #fff4e0; border-left: 4px solid #b26a00; padding: 8px 12px; margin-top: 12px; }
        .signature { margin-top: 40px; text-align: right; }
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
            <div>Bon n° {{ $demande->numero }}</div>
            <div>{{ $demande->created_at->format('d/m/Y à H:i') }}</div>
        </div>
    </div>

    <h1>Bon d'analyses</h1>
    <div class="sous-titre">À présenter au laboratoire <strong>{{ $demande->etablissement?->nom }}</strong></div>

    <div class="bloc">
        <strong>{{ $demande->patient?->full_name }}</strong>
        @if($demande->patient?->age !== null) · {{ $demande->patient->age }} ans @endif
        · {{ $demande->patient?->gender }}
        @if($demande->patient?->identifiant_national_sante)<div>Identifiant : {{ $demande->patient->identifiant_national_sante }}</div>@endif
        <div>Prescripteur : {{ $demande->prescripteur_externe ?? '—' }}</div>
        @if($demande->renseignements_cliniques)<div>Renseignements : {{ $demande->renseignements_cliniques }}</div>@endif
        @if($demande->grossesse)
            <div>Patiente enceinte @if($demande->semaines_amenorrhee) — {{ $demande->semaines_amenorrhee }} SA @endif</div>
        @endif
        @if($demande->urgence)<div style="color:#c0262d"><strong>URGENT</strong></div>@endif
    </div>

    <table>
        <thead><tr><th>Examen</th><th>Échantillon</th><th style="width:110px">À jeun</th></tr></thead>
        <tbody>
        @foreach($demande->examens as $ligne)
            <tr>
                <td>{{ $ligne->examen_nom }}</td>
                <td>{{ $ligne->examen?->type_echantillon ?? '—' }}</td>
                <td>{{ $ligne->examen?->a_jeun ? 'Oui' : 'Non' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="consignes">
        Présentez ce bon au laboratoire. {{ $demande->mode_facturation->value === 'labo' ? 'Le règlement se fait au laboratoire.' : 'Les analyses sont prises en charge par la clinique.' }}
        @if($demande->examens->contains(fn ($l) => $l->examen?->a_jeun))
            Certains examens exigent d'être <strong>à jeun</strong> : ne rien manger 8 à 12 heures avant le prélèvement, l'eau reste autorisée.
        @endif
    </div>

    @if($demande->consentement_partage_le)
        <p class="consignes" style="background:#e6f4f1; border-left-color:#087f6b">
            Le patient a été informé le {{ $demande->consentement_partage_le->format('d/m/Y') }} que ses analyses sont confiées à ce laboratoire.
        </p>
    @endif

    <div class="signature">Cachet et signature de la clinique<br><br><br>_______________________</div>
</body>
</html>
