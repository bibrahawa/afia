<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $document->type->libelle() }} — {{ $document->numero }}</title>
    {{--
        Document officiel remis au patient : sobre, noir sur blanc, lisible
        une fois photocopié. Autonome (aucune feuille de style du back-office).
    --}}
    <style>
        @page { size: A4; margin: 16mm 18mm; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef0f3; color: #1f2937; font-family: Arial, "Helvetica Neue", sans-serif; font-size: 13px; line-height: 1.55; }
        .feuille { width: 210mm; min-height: 297mm; margin: 24px auto; padding: 18mm 20mm; background: #fff; box-shadow: 0 6px 24px rgba(17, 24, 39, .12); display: flex; flex-direction: column; }

        .barre { position: sticky; top: 0; z-index: 2; display: flex; justify-content: center; gap: 10px; padding: 12px; background: #fff; border-bottom: 1px solid #e5e7eb; }
        .barre button, .barre a { display: inline-flex; align-items: center; gap: 8px; min-height: 38px; padding: 0 18px; border: 1px solid #0f766e; border-radius: 8px; background: #0f766e; color: #fff; font: inherit; font-weight: 700; cursor: pointer; text-decoration: none; }
        .barre a { background: #fff; color: #374151; border-color: #d1d5db; }

        .entete { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; padding-bottom: 12px; border-bottom: 2px solid #111827; }
        .entete img { max-height: 64px; max-width: 180px; display: block; margin-bottom: 6px; }
        .etablissement strong { display: block; font-size: 15px; color: #111827; }
        .etablissement span { color: #4b5563; font-size: 12px; }
        .date { text-align: right; font-size: 12px; color: #4b5563; }
        .date strong { display: block; color: #111827; font-size: 13px; }

        h1 { margin: 34px 0 4px; text-align: center; font-size: 20px; letter-spacing: .06em; text-transform: uppercase; color: #111827; }
        .numero { text-align: center; color: #6b7280; font-size: 12px; margin-bottom: 28px; }

        .annule { margin: 0 0 20px; padding: 10px 14px; border: 2px solid #b91c1c; border-radius: 6px; color: #b91c1c; font-weight: 700; text-align: center; }

        .contenu { flex: 1; min-height: 60mm; font-size: 14px; line-height: 1.9; white-space: pre-wrap; }
        .periode { margin: 18px 0 0; padding: 10px 14px; border-left: 3px solid #111827; background: #f9fafb; }

        .signature { display: flex; justify-content: flex-end; margin-top: 36px; }
        .signature div { width: 75mm; text-align: center; }
        .signature .cadre { height: 30mm; margin-top: 8px; border: 1px dashed #9ca3af; border-radius: 6px; color: #9ca3af; font-size: 11px; display: grid; place-items: end center; padding-bottom: 6px; }

        .pied { margin-top: 24px; padding-top: 8px; border-top: 1px solid #d1d5db; color: #6b7280; font-size: 10.5px; display: flex; justify-content: space-between; gap: 12px; }

        /* Filigrane d'un document annulé */
        .feuille.est-annule { position: relative; }
        .feuille.est-annule::after { content: "ANNULÉ"; position: absolute; top: 45%; left: 50%; transform: translate(-50%, -50%) rotate(-24deg); font-size: 96px; font-weight: 800; color: rgba(185, 28, 28, .10); letter-spacing: .1em; pointer-events: none; }

        @media print {
            body { background: #fff; }
            .barre { display: none; }
            .feuille { width: auto; min-height: calc(297mm - 32mm); margin: 0; padding: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="barre">
        <button type="button" onclick="window.print()">Imprimer</button>
        <a href="javascript:window.close()">Fermer</a>
    </div>

    <div class="feuille {{ $document->annule ? 'est-annule' : '' }}">
        <header class="entete">
            <div class="etablissement">
                @if($identite->logoDocumentsWeb())<img src="{{ $identite->logoDocumentsWeb() }}" alt="Logo">@endif
                <strong>{{ $identite->nom }}</strong>
                <span>{{ $identite->coordonnees() }}</span>
            </div>
            <div class="date">
                <strong>{{ $document->created_at->format('d/m/Y') }}</strong>
                @if($document->medecin){{ $document->medecin->nom_affiche }}@endif
            </div>
        </header>

        <h1>{{ $document->type->libelle() }}</h1>
        <div class="numero">N° {{ $document->numero }}</div>

        @if($document->annule)
            <div class="annule">DOCUMENT ANNULÉ — {{ $document->motif_annulation }}</div>
        @endif

        <div class="contenu">{{ $document->contenu }}</div>

        @if($document->date_debut && $document->date_fin)
            <p class="periode"><strong>Période :</strong> du {{ $document->date_debut->format('d/m/Y') }} au {{ $document->date_fin->format('d/m/Y') }} inclus
                ({{ $document->jours }} jour{{ $document->jours > 1 ? 's' : '' }}).</p>
        @endif

        <div class="signature">
            <div>
                Fait à {{ $identite->ville ?? 'Conakry' }}, le {{ $document->created_at->format('d/m/Y') }}
                @php $signatureMedecin = \App\Support\Etablissement\IdentiteDocument::signatureMedecinData($document->medecin); @endphp
                @if($signatureMedecin)
                    {{-- Signature PROPRE du médecin (déposée depuis son profil) : jamais celle d'un autre. --}}
                    <div class="cadre" style="border:0"><img src="{{ $signatureMedecin }}" alt="Signature" style="max-width:70mm; max-height:28mm; object-fit:contain"></div>
                @else
                    <div class="cadre">Signature et cachet du médecin</div>
                @endif
            </div>
        </div>

        <footer class="pied">
            <span>Patient : {{ $document->patient->full_name }}@if($document->patient->age !== null), {{ $document->patient->age }} ans @endif</span>
            <span>Document n° {{ $document->numero }} conservé au dossier de {{ $identite->nom }}.</span>
        </footer>
    </div>
</body>
</html>
