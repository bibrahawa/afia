{{-- Page autonome (sans layout) : optimisée pour étiquettes thermiques 50×25 mm, imprimable aussi en A4. --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Étiquettes {{ $demande->numero }}</title>
    <style>
        @page { size: 50mm 25mm; margin: 0; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; }
        .etiquette { width: 50mm; height: 25mm; box-sizing: border-box; padding: 1mm 2mm; page-break-after: always; overflow: hidden; }
        .ligne1 { font-size: 8pt; font-weight: bold; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .ligne2 { font-size: 6.5pt; white-space: nowrap; overflow: hidden; }
        .code svg { width: 100%; height: 9mm; display: block; }
        .num { font-size: 7pt; text-align: center; letter-spacing: .5px; }
        .outils { padding: 10px; font-family: sans-serif; }
        @media print { .outils { display: none; } }
        @media screen { body { background: #eee; } .etiquette { background: #fff; margin: 8px; border: 1px dashed #999; display: inline-block; vertical-align: top; } }
    </style>
</head>
<body>
    <div class="outils">
        <button onclick="window.print()">Imprimer</button>
        {{ $demande->echantillons->count() }} étiquette(s) — {{ $demande->numero }}
    </div>
    @foreach($demande->echantillons as $e)
        <div class="etiquette">
            <div class="ligne1">{{ \Illuminate\Support\Str::limit(mb_strtoupper($demande->patient->last_name) . ' ' . $demande->patient->first_name, 32) }}</div>
            <div class="ligne2">{{ $demande->patient->gender === 'Femme' ? 'F' : ($demande->patient->gender === 'Homme' ? 'M' : '') }} · {{ $ageTexte }} · {{ $demande->created_at->format('d/m/y') }} @if($demande->urgence)<strong>URG</strong>@endif</div>
            <div class="code">{!! \App\Support\Labo\CodeBarreItf::svg($e->code_barres, 40, 1.2) !!}</div>
            <div class="num">{{ $e->code_barres }} · {{ \App\Models\Labo\LaboExamen::TUBES[$e->tube] ?? \App\Models\Labo\LaboExamen::TYPES_ECHANTILLON[$e->type_echantillon] ?? '' }}</div>
            <div class="ligne2">{{ \Illuminate\Support\Str::limit($e->examens->map(fn ($x) => $x->examen?->abreviation ?: $x->examen_nom)->implode(' '), 40) }}</div>
        </div>
    @endforeach
    <script>if (new URLSearchParams(location.search).has('imprimer')) window.print();</script>
</body>
</html>
