<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Ordonnance</title>
@include('documents._ticket')
@include('documents._ticket-ecran')
</head>
<body onload="setTimeout(function () { window.print(); }, 300)">
<div class="d-outils"><button type="button" onclick="window.print()">Imprimer</button><a href="javascript:history.back()">Retour</a></div>
@include('documents._ticket-entete', ['type' => 'Ordonnance', 'numero' => 'ORD-' . str_pad($consultation->id, 6, '0', STR_PAD_LEFT), 'date' => $consultation->created_at, 'patient' => $consultation->patient, 'medecin' => $consultation->medecin])

@forelse($consultation->medicaments as $i => $med)
    @php
        $p = $med->pivot;
        $posologie = collect([$p->dose ?: $med->dosage, $p->frequence ?: $med->frequence, ($p->duree ?: $med->duree) ? 'pendant ' . ($p->duree ?: $med->duree) : null])->filter()->implode(' · ');
    @endphp
    <div style="padding:1.2mm 0; border-bottom:.5pt dashed #000">
        <strong>{{ $i + 1 }}. {{ $med->nom }}</strong> <span style="float:right">Qté {{ (int) ($p->quantity ?? 1) }}</span>
        @if($posologie)<div>{{ $posologie }}</div>@endif
        @if($p->instructions ?: $med->instructions)<div style="font-style:italic">{{ $p->instructions ?: $med->instructions }}</div>@endif
    </div>
@empty
    <div class="t-centre">Aucun médicament prescrit.</div>
@endforelse

<div style="margin-top:8mm; border-top:.5pt solid #000; padding-top:1mm; text-align:center">Signature et cachet<br><strong>{{ $consultation->medecin?->nom_affiche }}</strong></div>
<div class="t-merci">Édité le {{ now()->format('d/m/Y à H:i') }}</div>
</body>
</html>
