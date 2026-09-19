<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Demande d'examens · {{ $consultation->patient->getFullName() }}</title>
@include('documents._a5')
@include('documents._ecran')
<style>
    .e-ligne td { padding: 2.2mm 0; border-bottom: .5pt solid #e5e7eb; vertical-align: top; }
    .e-case { width: 7mm; }
    .e-case span { display: inline-block; width: 3.2mm; height: 3.2mm; border: .8pt solid #374151; border-radius: .6mm; }
    .e-nom { font-size: 10pt; font-weight: bold; color: #111827; }
    .e-rens { margin-top: 3mm; padding: 2.5mm 3mm; background: #f3f6f6; font-size: 8.5pt; }
</style>
</head>
<body onload="setTimeout(function () { window.print(); }, 300)">
<div class="d-outils"><button type="button" onclick="window.print()">Imprimer</button><a href="javascript:history.back()">Retour</a></div>

@include('documents._pied')
@include('documents._entete', ['type' => "Demande d'examens", 'numero' => 'EXA-' . str_pad($consultation->id, 6, '0', STR_PAD_LEFT), 'date' => $consultation->created_at, 'pdf' => false])
@include('documents._personnes', ['patient' => $consultation->patient, 'medecin' => $consultation->medecin, 'service' => $consultation->department?->name])

@if($consultation->motif || $consultation->diagnostic)
    {{-- Renseignements cliniques : utiles au laboratoire pour interpréter les résultats. --}}
    <div class="e-rens">
        @if($consultation->motif)<div><span class="d-etiquette">Motif</span> {{ $consultation->motif }}</div>@endif
        @if($consultation->diagnostic)<div><span class="d-etiquette">Hypothèse</span> {{ $consultation->diagnostic }}</div>@endif
    </div>
@endif

<div class="d-titre-section">Examens demandés</div>
<table>
    @forelse($consultation->tests as $test)
        <tr class="e-ligne"><td class="e-case"><span></span></td>
            <td><div class="e-nom">{{ $test->name }}</div>@if($test->description)<span class="d-sous">{{ $test->description }}</span>@endif</td></tr>
    @empty
        <tr><td><div class="d-vide" style="margin-top:0">Aucun examen demandé.</div></td></tr>
    @endforelse
</table>

<div class="d-signature">
    <div class="d-etiquette">Signature et cachet du médecin</div>
    <div class="d-ligne"></div>
    <div class="d-petit"><strong>{{ $consultation->medecin?->nom_affiche }}</strong></div>
</div>
</body>
</html>
