<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Facture</title>@include('documents._ticket')</head>
<body>
@php
    $gnf = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $invoice = $invoiceData['invoice'] ?? null;
    $numero = $consultation->transaction?->invoice_no ?: ($invoice ? 'FAC-' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT) : null);
    $reclamations = $invoice ? $invoice->insuranceClaims()->with('insuranceCompany')->get() : collect();
    $statut = $invoiceData['status'] ?? null;
@endphp
@include('documents._ticket-entete', ['type' => 'Facture', 'numero' => $numero, 'date' => $consultation->created_at, 'patient' => $consultation->patient, 'medecin' => $consultation->medecin])

@if(! $invoice)
    <div class="t-centre">Aucune facture établie pour cette consultation.</div>
@else
    <table class="t-lignes">
        @foreach($invoice->items as $item)
            <tr>
                <td>{{ (int) $item->quantity > 1 ? (int) $item->quantity . ' × ' : '' }}{{ $item->description }}
                    @if((float) $item->insurance_covered_amount > 0)<span class="t-sous">dont assurance {{ $gnf($item->insurance_covered_amount) }}</span>@endif</td>
                <td class="t-n">{{ $gnf($item->total_amount) }}</td>
            </tr>
        @endforeach
    </table>
    <div class="t-sep-plein"></div>
    <table class="t-lignes">
        <tr><td>Total des actes</td><td class="t-n">{{ $gnf($invoice->total_amount) }}</td></tr>
        @foreach($reclamations as $r)
            <tr><td>Part {{ $r->insuranceCompany?->name }}</td><td class="t-n">−{{ $gnf($r->claimed_amount) }}</td></tr>
        @endforeach
        <tr><td><strong>À votre charge</strong></td><td class="t-n"><strong>{{ $gnf($invoiceData['patient_amount']) }}</strong></td></tr>
        <tr><td>Déjà payé</td><td class="t-n">{{ $gnf($invoiceData['paid_amount']) }}</td></tr>
        <tr class="t-fort"><td>RESTE À PAYER</td><td class="t-n">{{ $gnf($invoiceData['remaining']) }} GNF</td></tr>
    </table>
    <div class="t-cadre">{{ $statut === 'paid' ? 'Payée' : ($statut === 'partial' ? 'Paiement partiel' : 'À payer') }}</div>
@endif
<div class="t-merci">{{ $identite->messageFacture ?: 'Merci de votre confiance.' }}<br>Édité le {{ now()->format('d/m/Y à H:i') }}</div>
</body>
</html>
