<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Reçu de paiement</title>
@include('documents._a5')
</head>
<body>
@php
    $gnf = fn ($v) => number_format((float) $v, 0, ',', ' ') . ' GNF';
    // CORRIGÉ : les paiements annulés étaient comptés dans les totaux par mode de paiement.
    // Seuls les paiements du PATIENT non annulés (les règlements d'assurance ne figurent pas sur son reçu).
    $paiements = ($consultation->transaction?->paiements ?? collect())
        ->filter(fn ($p) => ($p->type ?? 'paiement') === 'paiement' && empty($p->annule_le))
        ->sortBy('created_at')->values();
    $dernier = $paiements->last();
    $totalPaye = (float) ($invoiceData['paid_amount'] ?? $paiements->sum('montant'));
    $numero = $dernier?->paiement_no ?: 'REC-' . str_pad($consultation->id, 6, '0', STR_PAD_LEFT);
    $statut = $invoiceData['status'] ?? null;
    $modes = $paiements->groupBy(fn ($p) => trim((string) $p->source) ?: 'Non précisé')->map->sum('montant');
@endphp

@include('documents._pied')
@include('documents._entete', ['type' => 'Reçu', 'numero' => $numero, 'date' => $dernier?->created_at ?? now(), 'heure' => true])
@include('documents._personnes', ['patient' => $consultation->patient, 'medecin' => $consultation->medecin, 'service' => $consultation->department?->name])

@if(! $invoiceData)
    <div class="d-vide">Aucune facture n'a encore été établie pour cette consultation.</div>
@else
    <div class="d-titre-section">Paiements reçus</div>
    @if($paiements->isEmpty())
        <div class="d-vide" style="margin-top:0">Aucun paiement enregistré.</div>
    @else
        <table class="d-lignes">
            <thead><tr><th>Date</th><th>Mode</th><th>Référence</th><th class="n">Montant</th></tr></thead>
            <tbody>
                @foreach($paiements as $p)
                    <tr>
                        <td>{{ $p->created_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ $p->source ?: 'Non précisé' }}</td>
                        <td>{{ $p->paiement_no }}</td>
                        <td class="n">{{ $gnf($p->montant) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Tampon et cachet de la caisse à gauche des totaux : le reçu tient sur une page A5. --}}
    <table style="margin-top:3mm"><tr>
        <td style="width:40%; vertical-align:bottom; padding-right:4mm">
            @if($statut === 'paid')<div class="d-cachet d-paye">Soldé</div>
            @elseif($statut === 'partial')<div class="d-cachet d-partiel">Solde restant</div>
            @else<div class="d-cachet d-du">Non payé</div>@endif
            <div style="margin-top:3mm">@include('documents._validation', ['pdf' => true, 'etiquette' => 'La caisse', 'avecSignature' => false, 'nom' => (string) auth()->user()?->name, 'fonction' => ''])</div>
        </td>
        <td style="vertical-align:top">
    <table class="d-totaux" style="width:100%; margin:0">
        <tr><td>À votre charge</td><td class="n">{{ $gnf($invoiceData['patient_amount']) }}</td></tr>
        <tr><td><strong>Total payé</strong></td><td class="n"><strong>{{ $gnf($totalPaye) }}</strong></td></tr>
        @if($modes->count() > 1)
            @foreach($modes as $mode => $montant)
                <tr><td class="d-petit">&nbsp;&nbsp;dont {{ mb_strtolower($mode) }}</td><td class="n d-petit">{{ $gnf($montant) }}</td></tr>
            @endforeach
        @endif
        <tr class="d-fort"><td>Reste à payer</td><td class="n">{{ $gnf($invoiceData['remaining']) }}</td></tr>
    </table>
        </td>
    </tr></table>

    {{-- CORRIGÉ : affichait littéralement « À compléter ». --}}
    <div class="d-lettres">Arrêté le présent reçu à la somme de <strong>{{ \App\Support\MontantEnLettres::gnf($totalPaye) }}</strong>.</div>


@endif
</body>
</html>
