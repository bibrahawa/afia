<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Ordonnance · {{ $consultation->patient->getFullName() }}</title>
@include('documents._a5')
@include('documents._ecran')
<style>
    .o-ligne { border-bottom: .5pt solid #e5e7eb; page-break-inside: avoid; }
    .o-ligne td { padding: 3mm 0; }
    .o-num { width: 7mm; color: #0f766e; font-weight: bold; vertical-align: top; }
    .o-nom { font-size: 10.5pt; font-weight: bold; color: #111827; }
    .o-forme { color: #6b7280; font-size: 8pt; font-weight: normal; }
    .o-poso { margin-top: .8mm; font-size: 9.2pt; }
    .o-instr { margin-top: .6mm; color: #374151; font-size: 8.2pt; font-style: italic; }
    .o-qte { width: 22mm; text-align: right; vertical-align: top; font-size: 8pt; color: #374151; white-space: nowrap; }
</style>
</head>
<body onload="setTimeout(function () { window.print(); }, 300)">
<div class="d-outils"><button type="button" onclick="window.print()">Imprimer</button><a href="javascript:history.back()">Retour</a></div>
@php
    $medicaments = $consultation->medicaments;
    $formes = ['COMPRIMÉ' => 'comprimé', 'GÉLULE' => 'gélule', 'SIROP' => 'sirop', 'INJECTION' => 'injectable', 'PERFUSION' => 'perfusion', 'CRÈME' => 'crème', 'POMMADE' => 'pommade', 'SUPPOSITOIRE' => 'suppositoire', 'GOUTTES' => 'gouttes', 'SPRAY' => 'spray', 'INHALATEUR' => 'inhalateur'];
@endphp

@include('documents._pied')
@include('documents._entete', ['type' => 'Ordonnance', 'numero' => 'ORD-' . str_pad($consultation->id, 6, '0', STR_PAD_LEFT), 'date' => $consultation->created_at, 'pdf' => false])
@include('documents._personnes', ['patient' => $consultation->patient, 'medecin' => $consultation->medecin, 'service' => $consultation->department?->name])

<div class="d-titre-section">Prescription</div>
@forelse($medicaments as $i => $med)
    @php
        $p = $med->pivot;
        $dose = $p->dose ?: $med->dosage;
        $posologie = collect([$dose, $p->frequence ?: $med->frequence, ($p->duree ?: $med->duree) ? 'pendant ' . ($p->duree ?: $med->duree) : null])->filter()->implode(' · ');
        $quantite = (int) ($p->quantity ?? 1);
    @endphp
    <table class="o-ligne"><tr>
        <td class="o-num">{{ $i + 1 }}.</td>
        <td>
            <div class="o-nom">{{ $med->nom }}@if($med->dosage && $med->dosage !== $dose) {{ $med->dosage }}@endif @if($med->forme)<span class="o-forme">· {{ $formes[$med->forme] ?? mb_strtolower($med->forme) }}</span>@endif</div>
            @if($posologie)<div class="o-poso">{{ $posologie }}</div>@endif
            @if($p->instructions ?: $med->instructions)<div class="o-instr">{{ $p->instructions ?: $med->instructions }}</div>@endif
        </td>
        <td class="o-qte">Qté : <strong>{{ $quantite }}</strong></td>
    </tr></table>
@empty
    <div class="d-vide" style="margin-top:0">Aucun médicament prescrit.</div>
@endforelse

<div class="d-signature">
    <div class="d-etiquette">Signature et cachet du médecin</div>
    <div class="d-ligne"></div>
    <div class="d-petit"><strong>{{ $consultation->medecin?->nom_affiche }}</strong></div>
</div>
</body>
</html>
