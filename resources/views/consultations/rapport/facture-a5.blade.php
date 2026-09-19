<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Facture</title>
@include('documents._a5')
</head>
<body>
@php
    $gnf = fn ($v) => number_format((float) $v, 0, ',', ' ') . ' GNF';
    $invoice = $invoiceData['invoice'] ?? null;
    $numero = $consultation->transaction?->invoice_no ?: ($invoice ? 'FAC-' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT) : null);
    $reclamations = $invoice ? $invoice->insuranceClaims()->with('insuranceCompany', 'patientInsurance')->get() : collect();
    $statut = $invoiceData['status'] ?? null;
@endphp

@include('documents._pied')
@include('documents._entete', ['type' => 'Facture', 'numero' => $numero, 'date' => $consultation->created_at])
@include('documents._personnes', ['patient' => $consultation->patient, 'medecin' => $consultation->medecin, 'service' => $consultation->department?->name])

@if(! $invoice)
    {{-- CORRIGÉ : sans facture, la page plantait (accès à un tableau nul). --}}
    <div class="d-vide">Aucune facture n'a encore été établie pour cette consultation.<br>Passez à la caisse pour la créer.</div>
@else
    <div class="d-titre-section">Détail des actes</div>
    <table class="d-lignes">
        <thead><tr><th>Acte</th><th class="n">Qté</th><th class="n">Montant</th><th class="n">À votre charge</th></tr></thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}
                        @if((float) $item->insurance_covered_amount > 0)<span class="d-assurance">Pris en charge par l'assurance : {{ $gnf($item->insurance_covered_amount) }}</span>@endif</td>
                    <td class="n">{{ (int) $item->quantity }}</td>
                    <td class="n">{{ $gnf($item->total_amount) }}</td>
                    <td class="n">{{ $gnf((float) $item->insurance_covered_amount > 0 ? $item->patient_amount : $item->total_amount) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Tampon et validation à gauche des totaux (espace libre) : la facture tient sur une page A5. --}}
    <table style="margin-top:3mm"><tr>
        <td style="width:40%; vertical-align:bottom; padding-right:4mm">
            @if($statut === 'paid')<div class="d-cachet d-paye">Payée</div>
            @elseif($statut === 'partial')<div class="d-cachet d-partiel">Paiement partiel</div>
            @else<div class="d-cachet d-du">À payer</div>@endif
            <div style="margin-top:3mm">@include('documents._validation', ['pdf' => true, 'etiquette' => 'La direction'])</div>
        </td>
        <td style="vertical-align:top">
    <table class="d-totaux" style="width:100%; margin:0">
        <tr><td>Total des actes</td><td class="n">{{ $gnf($invoice->total_amount) }}</td></tr>
        @foreach($reclamations as $r)
            <tr><td>Part {{ $r->insuranceCompany?->name }}@if($r->patientInsurance?->policy_number)<span class="d-sous">carte {{ $r->patientInsurance->policy_number }}</span>@endif</td><td class="n">− {{ $gnf($r->claimed_amount) }}</td></tr>
        @endforeach
        <tr><td><strong>À votre charge</strong></td><td class="n"><strong>{{ $gnf($invoiceData['patient_amount']) }}</strong></td></tr>
        <tr><td>Déjà payé</td><td class="n">{{ $gnf($invoiceData['paid_amount']) }}</td></tr>
        <tr class="d-fort"><td>Reste à payer</td><td class="n">{{ $gnf($invoiceData['remaining']) }}</td></tr>
    </table>
        </td>
    </tr></table>


@endif
</body>
</html>
