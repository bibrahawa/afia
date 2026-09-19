{{--
    Étiquettes des tubes. Page autonome (sans layout).
    Deux formats :
      - par défaut : imprimante thermique, une étiquette de 50 × 25 mm par page ;
      - ?format=a4 : planche A4 de 3 × 10 étiquettes de 50 × 25 mm, à découper
        (pour les laboratoires sans imprimante d'étiquettes).
    Les dimensions de l'étiquette ne changent pas d'un format à l'autre.
--}}
@php
    $a4 = request('format') === 'a4';
    $patient = $demande->patient;
    $nom = \Illuminate\Support\Str::limit(mb_strtoupper((string) $patient->last_name) . ' ' . $patient->first_name, 30);
    $sexe = $patient->gender === 'Femme' ? 'F' : ($patient->gender === 'Homme' ? 'M' : '');

    // Examens en clair et court : abréviation, sinon code du catalogue (NFS, GLY,
    // CREA…), sinon nom. Au-delà de la place disponible : « +N » au lieu d'un mot coupé.
    $examensCourts = function ($echantillon) {
        $libelles = $echantillon->examens->map(fn ($x) => $x->examen?->abreviation ?: ($x->examen?->code ?: $x->examen_nom))->values();
        $texte = '';
        foreach ($libelles as $i => $libelle) {
            $suivant = $texte === '' ? $libelle : $texte . ' · ' . $libelle;
            if (mb_strlen($suivant) > 34) {
                return $texte . ' +' . ($libelles->count() - $i);
            }
            $texte = $suivant;
        }
        return $texte;
    };
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Étiquettes {{ $demande->numero }}</title>
    <style>
        @if($a4)
            @page { size: A4; margin: 10mm; }
        @else
            @page { size: 50mm 25mm; margin: 0; }
        @endif
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; color: #000; }

        /* ---- L'étiquette : 50 × 25 mm, identique dans les deux formats */
        .etiquette { width: 50mm; height: 25mm; padding: 1.2mm 2mm 1mm; overflow: hidden; background: #fff; }
        .haut { display: flex; align-items: baseline; justify-content: space-between; gap: 1mm; }
        .nom { flex: 1; min-width: 0; font-size: 8pt; font-weight: bold; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .urgent { flex: none; padding: 0 1mm; background: #000; color: #fff; font-size: 6.5pt; font-weight: bold; letter-spacing: .3px; }
        .infos { font-size: 6.5pt; white-space: nowrap; overflow: hidden; }
        .code svg { display: block; width: 100%; height: 9mm; }
        .num { font-size: 7pt; text-align: center; letter-spacing: .5px; white-space: nowrap; overflow: hidden; }
        .num b { letter-spacing: 1px; }
        .examens { font-size: 6.5pt; font-weight: bold; white-space: nowrap; overflow: hidden; }

        /* ---- Thermique : une étiquette par page */
        .format-thermique .etiquette { page-break-after: always; }
        .format-thermique .etiquette:last-child { page-break-after: auto; }

        /* ---- Planche A4 : grille 3 × 10 avec traits de coupe */
        .format-a4 .planche { display: grid; grid-template-columns: repeat(3, 50mm); grid-auto-rows: 25mm; gap: 2mm 8mm; justify-content: center; }
        .format-a4 .etiquette { outline: .2mm dashed #999; }
        .format-a4 .etiquette:nth-child(30n) { page-break-after: always; }

        /* ---- Écran uniquement */
        .outils { display: none; }
        @media screen {
            body { background: #f4f6f6; font-family: -apple-system, "Segoe UI", Roboto, Arial, sans-serif; }
            .outils { position: sticky; top: 0; z-index: 5; display: flex; flex-wrap: wrap; align-items: center; gap: 10px 16px; padding: 14px 20px; background: #fff; border-bottom: 1px solid #e5e7eb; }
            .outils h1 { margin: 0; color: #111827; font-size: 16px; }
            .outils p { margin: 2px 0 0; color: #6b7280; font-size: 13px; }
            .bascule { display: inline-flex; padding: 3px; border-radius: 10px; background: #f3f4f6; }
            .bascule a { padding: 7px 12px; border-radius: 8px; color: #374151; font-size: 13px; font-weight: 600; text-decoration: none; }
            .bascule a.actif { background: #fff; color: #115e59; box-shadow: 0 1px 2px rgba(0, 0, 0, .08); }
            .imprimer { margin-left: auto; min-height: 42px; padding: 0 20px; border: 0; border-radius: 10px; background: #0f766e; color: #fff; font-size: 15px; font-weight: 700; cursor: pointer; }
            .imprimer:hover { background: #115e59; }
            .conseil { width: 100%; margin: 0; padding: 10px 12px; border-radius: 8px; background: #fffbeb; color: #78350f; font-size: 13px; }
            .apercu { padding: 24px 20px; }
            .format-thermique .planche { display: flex; flex-wrap: wrap; gap: 14px; }
            .etiquette { border-radius: 2mm; box-shadow: 0 0 0 1px #d1d5db, 0 2px 6px rgba(0, 0, 0, .06); }
            .format-a4 .planche { width: 190mm; margin: 0 auto; padding: 10mm 0; background: #fff; box-shadow: 0 2px 10px rgba(0, 0, 0, .08); }
            .format-a4 .etiquette { box-shadow: none; border-radius: 0; }
        }
    </style>
</head>
<body class="{{ $a4 ? 'format-a4' : 'format-thermique' }}">
    <div class="outils">
        <div>
            <h1>{{ $demande->echantillons->count() }} étiquette{{ $demande->echantillons->count() > 1 ? 's' : '' }} · {{ $demande->numero }}</h1>
            <p>{{ $patient->full_name }}</p>
        </div>
        <nav class="bascule" aria-label="Format d'impression">
            <a href="{{ request()->fullUrlWithQuery(['format' => null, 'imprimer' => null]) }}" class="{{ $a4 ? '' : 'actif' }}">Imprimante d'étiquettes</a>
            <a href="{{ request()->fullUrlWithQuery(['format' => 'a4', 'imprimer' => null]) }}" class="{{ $a4 ? 'actif' : '' }}">Planche A4</a>
        </nav>
        <button type="button" class="imprimer" onclick="window.print()">Imprimer</button>
        <p class="conseil">
            @if($a4)
                Dans la fenêtre d'impression : format <strong>A4</strong>, échelle <strong>100 %</strong>. Découpez le long des pointillés et collez chaque étiquette sur le tube indiqué.
            @else
                Dans la fenêtre d'impression : choisissez l'imprimante d'étiquettes, marges <strong>Aucune</strong>, échelle <strong>100 %</strong>. Une étiquette de 50 × 25 mm par tube.
            @endif
        </p>
    </div>

    <div class="apercu">
        <div class="planche">
            @foreach($demande->echantillons as $e)
                <div class="etiquette">
                    <div class="haut">
                        <span class="nom">{{ $nom }}</span>
                        @if($demande->urgence)<span class="urgent">URGENT</span>@endif
                    </div>
                    <div class="infos">{{ $sexe }} · {{ $ageTexte }} · {{ $demande->created_at->format('d/m/y') }}</div>
                    <div class="code">{!! \App\Support\Labo\CodeBarreItf::svg($e->code_barres, 40, 1.2) !!}</div>
                    <div class="num"><b>{{ $e->code_barres }}</b> · {{ \App\Models\Labo\LaboExamen::TUBES[$e->tube] ?? \App\Models\Labo\LaboExamen::TYPES_ECHANTILLON[$e->type_echantillon] ?? '' }}</div>
                    <div class="examens">{{ $examensCourts($e) }}</div>
                </div>
            @endforeach
        </div>
    </div>
    <script>if (new URLSearchParams(location.search).has('imprimer')) window.print();</script>
</body>
</html>
