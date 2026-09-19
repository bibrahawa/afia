<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Facture d'hospitalisation {{ $factureNo }}</title>
@include('documents._a5')
<style>
    /* Même charte que les documents A5, sur une page A4. */
    @page { size: A4 portrait; margin: 14mm 14mm 20mm; }
    body { font-size: 9.5pt; }
    .d-pied { bottom: -14mm; }
    .h-sejour td { width: 25%; padding: 2.5mm 3mm; background: #f3f6f6; vertical-align: top; }
    .h-sejour td + td { border-left: 2mm solid #fff; }
    .h-valeur { font-size: 10pt; font-weight: bold; color: #111827; }
    .d-totaux { width: 50%; margin-left: 50%; }
</style>
</head>
<body>
@php
    $gnf = fn ($v) => number_format((float) $v, 0, ',', ' ') . ' GNF';
    $patient = $hospitalisation->patient;
    $entree = \Illuminate\Support\Carbon::parse($hospitalisation->date_entree);
    $sortie = $hospitalisation->date_sortie_effective ? \Illuminate\Support\Carbon::parse($hospitalisation->date_sortie_effective) : null;
    $items = $hospitalisation->transaction?->invoice?->items ?? collect();
    $prixJour = (float) ($hospitalisation->chambre?->prix_par_jour ?? 0);
    // Parts et règlements : même calcul que la caisse (SoldeTransaction).
    $partPatient = $solde?->partPatient ?? (float) $total;
    $partAssurance = $solde?->partAssurance ?? 0.0;
    $paye = $solde?->payePatient ?? 0.0;
    $reste = $solde ? $solde->resteDuPatient() : (float) $total;
@endphp

@include('documents._pied')
@include('documents._entete', ['type' => 'Facture', 'numero' => $factureNo, 'date' => $sortie ?? now()])

<table class="d-personnes">
    <tr>
        <td>
            <div class="d-etiquette">Patient</div>
            <div class="d-nom">{{ $patient->getFullName() }}</div>
            @if($patient->identifiant_national_sante)<div class="d-petit">ID santé {{ $patient->identifiant_national_sante }}</div>@endif
        </td>
        <td>
            <div class="d-etiquette">Hospitalisation</div>
            <div class="d-nom">Chambre {{ $hospitalisation->chambre?->numero }}</div>
            <div class="d-petit">{{ $hospitalisation->chambre?->type }}</div>
        </td>
    </tr>
</table>

<table class="h-sejour" style="margin-top:2mm">
    <tr>
        <td><div class="d-etiquette">Entrée</div><div class="h-valeur">{{ $entree->format('d/m/Y') }}</div><div class="d-petit">{{ $entree->format('H:i') }}</div></td>
        <td><div class="d-etiquette">Sortie</div>
            @if($sortie)<div class="h-valeur">{{ $sortie->format('d/m/Y') }}</div><div class="d-petit">{{ $sortie->format('H:i') }}</div>
            @else<div class="h-valeur">En cours</div><div class="d-petit">facture provisoire</div>@endif</td>
        <td><div class="d-etiquette">Durée</div><div class="h-valeur">{{ (int) $nombreJours }} jour{{ $nombreJours > 1 ? 's' : '' }}</div></td>
        <td><div class="d-etiquette">Prix de la chambre</div><div class="h-valeur">{{ $gnf($prixJour) }}</div><div class="d-petit">par jour</div></td>
    </tr>
</table>

<div class="d-titre-section">Détail</div>
<table class="d-lignes">
    <thead><tr><th>Désignation</th><th class="n">Qté</th><th class="n">Prix unitaire</th><th class="n">Montant</th></tr></thead>
    <tbody>
        @forelse($items as $item)
            <tr>
                <td>{{ $item->description }}
                    @if((float) $item->insurance_covered_amount > 0)<span class="d-assurance">Pris en charge par l'assurance : {{ $gnf($item->insurance_covered_amount) }}</span>@endif</td>
                <td class="n">{{ (int) $item->quantity }}</td>
                <td class="n">{{ $gnf($item->unit_price ?? ((float) $item->total_amount / max(1, (int) $item->quantity))) }}</td>
                <td class="n">{{ $gnf($item->total_amount) }}</td>
            </tr>
        @empty
            {{-- Pas encore de facture détaillée : la chambre seule, comme avant. --}}
            <tr>
                <td>Hospitalisation · chambre {{ $hospitalisation->chambre?->numero }}</td>
                <td class="n">{{ (int) $nombreJours }}</td>
                <td class="n">{{ $gnf($prixJour) }}</td>
                <td class="n">{{ $gnf($total) }}</td>
            </tr>
        @endforelse
    </tbody>
</table>

<table class="d-totaux">
    <tr><td>Total</td><td class="n">{{ $gnf($total) }}</td></tr>
    @if($partAssurance > 0)<tr><td>Part assurance</td><td class="n">− {{ $gnf($partAssurance) }}</td></tr>@endif
    <tr><td><strong>À votre charge</strong></td><td class="n"><strong>{{ $gnf($partPatient) }}</strong></td></tr>
    <tr><td>Déjà payé</td><td class="n">{{ $gnf($paye) }}</td></tr>
    <tr class="d-fort"><td>Reste à payer</td><td class="n">{{ $gnf($reste) }}</td></tr>
</table>

<div class="d-lettres">Arrêtée la présente facture à la somme de <strong>{{ \App\Support\MontantEnLettres::gnf($partPatient) }}</strong> à la charge du patient.</div>

<table style="margin-top:5mm"><tr>
    <td style="width:50%; vertical-align:top">
        @if($reste < 1)<div class="d-cachet d-paye" style="width:60%">Payée</div>
        @elseif($paye > 0)<div class="d-cachet d-partiel" style="width:60%">Paiement partiel</div>
        @else<div class="d-cachet d-du" style="width:60%">À payer</div>@endif
    </td>
    <td style="vertical-align:top">
        @include('documents._validation', ['pdf' => true, 'etiquette' => 'La direction'])
    </td>
</tr></table>
</body>
</html>
