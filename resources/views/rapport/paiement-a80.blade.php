<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Reçu</title>@include('documents._ticket')</head>
<body>
@php
    $gnf = fn ($v) => number_format((float) $v, 0, ',', ' ');
    // Paiements du patient non annulés uniquement (voir le reçu A5).
    $paiements = ($consultation->transaction?->paiements ?? collect())
        ->filter(fn ($p) => ($p->type ?? 'paiement') === 'paiement' && empty($p->annule_le))->sortBy('created_at')->values();
    $dernier = $paiements->last();
    $totalPaye = (float) ($invoiceData['paid_amount'] ?? $paiements->sum('montant'));
    $statut = $invoiceData['status'] ?? null;
@endphp
@include('documents._ticket-entete', ['type' => 'Reçu', 'numero' => $dernier?->paiement_no, 'date' => $dernier?->created_at ?? now(), 'patient' => $consultation->patient, 'medecin' => null])

@if(! $invoiceData)
    <div class="t-centre">Aucune facture établie pour cette consultation.</div>
@else
    <table class="t-lignes">
        @forelse($paiements as $p)
            <tr><td>{{ $p->created_at?->format('d/m H:i') }} · {{ $p->source ?: 'Non précisé' }}<span class="t-sous">{{ $p->paiement_no }}</span></td><td class="t-n">{{ $gnf($p->montant) }}</td></tr>
        @empty
            <tr><td class="t-centre">Aucun paiement enregistré.</td></tr>
        @endforelse
    </table>
    <div class="t-sep-plein"></div>
    <table class="t-lignes">
        <tr><td>À votre charge</td><td class="t-n">{{ $gnf($invoiceData['patient_amount']) }}</td></tr>
        <tr><td><strong>Total payé</strong></td><td class="t-n"><strong>{{ $gnf($totalPaye) }}</strong></td></tr>
        <tr class="t-fort"><td>RESTE À PAYER</td><td class="t-n">{{ $gnf($invoiceData['remaining']) }} GNF</td></tr>
    </table>
    <div style="margin-top:2mm; font-size:6.8pt">Arrêté à la somme de <strong>{{ \App\Support\MontantEnLettres::gnf($totalPaye) }}</strong>.</div>
    <div class="t-cadre">{{ $statut === 'paid' ? 'Soldé' : ($statut === 'partial' ? 'Solde restant' : 'Non payé') }}</div>
    <div style="margin-top:2mm; font-size:6.8pt">Caisse : {{ auth()->user()?->name }}</div>
@endif
<div class="t-merci">{{ $identite->messageFacture ?: 'Merci de votre confiance.' }}<br>Édité le {{ now()->format('d/m/Y à H:i') }}</div>
</body>
</html>
