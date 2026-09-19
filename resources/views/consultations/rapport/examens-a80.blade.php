<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Demande d'examens</title>
@include('documents._ticket')
@include('documents._ticket-ecran')
</head>
<body onload="setTimeout(function () { window.print(); }, 300)">
<div class="d-outils"><button type="button" onclick="window.print()">Imprimer</button><a href="javascript:history.back()">Retour</a></div>
@include('documents._ticket-entete', ['type' => 'Examens', 'numero' => 'EXA-' . str_pad($consultation->id, 6, '0', STR_PAD_LEFT), 'date' => $consultation->created_at, 'patient' => $consultation->patient, 'medecin' => $consultation->medecin])

@if($consultation->motif || $consultation->diagnostic)
    <div style="margin-bottom:1.5mm">
        @if($consultation->motif)<div><strong>Motif :</strong> {{ $consultation->motif }}</div>@endif
        @if($consultation->diagnostic)<div><strong>Hypothèse :</strong> {{ $consultation->diagnostic }}</div>@endif
    </div>
    <div class="t-sep"></div>
@endif

@forelse($consultation->tests as $test)
    <div style="padding:1mm 0">☐ <strong>{{ $test->name }}</strong>@if($test->description)<span class="t-sous">{{ $test->description }}</span>@endif</div>
@empty
    <div class="t-centre">Aucun examen demandé.</div>
@endforelse

@php $signatureMedecin = \App\Support\Etablissement\IdentiteDocument::signatureMedecinData($consultation->medecin); @endphp
<div style="margin-top:{{ $signatureMedecin ? '2mm' : '8mm' }}; text-align:center">
    @if($signatureMedecin)<img src="{{ $signatureMedecin }}" alt="" style="max-width:40mm; max-height:13mm">@endif
    <div style="border-top:.5pt solid #000; padding-top:1mm">Signature et cachet<br><strong>{{ $consultation->medecin?->nom_affiche }}</strong></div>
</div>
<div class="t-merci">Édité le {{ now()->format('d/m/Y à H:i') }}</div>
</body>
</html>
