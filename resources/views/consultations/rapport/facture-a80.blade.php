<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Facture Ticket</title>

<style>
@page { size: 80mm auto; margin: 3mm; }

body {
    width: 74mm;
    font-family: Arial;
    font-size: 11px;
}

.center { text-align: center; }
.bold { font-weight: bold; }

.separator {
    border-top: 1px dashed #000;
    margin: 8px 0;
}

.item {
    margin-bottom: 8px;
}
.logo {
            text-align: center;
            margin-bottom: 5px;
        }

        .logo img {
            max-width: 48mm;
            max-height: 42px;
            object-fit: contain;
        }

</style>
</head>

<body onload="window.print()">
<div class="logo">
    @if($identite->logoPdf())<img src="{{ $identite->logoPdf() }}" alt="Logo">@endif
</div>
<div class="center bold">{{ $identite->nom }}</div>
<div class="center">{{ $identite->adresse }}</div>
<div class="center">{{ $identite->email }} / {{ $identite->contact }}</div>

<div class="separator"></div>

<div class="center bold">FACTURE</div>

<div>Date: {{ $consultation->created_at->format('d/m/Y') }}</div>
<div>Patient: {{ $consultation->patient->first_name }}</div>

<div class="separator"></div>

@foreach($invoiceData['invoice']->items as $item)
<div class="item">
    <div class="bold">{{ $item->description }}</div>

    @if($item->insurance_covered_amount > 0)
        <div style="color:green">
            Assurance -{{ number_format($item->insurance_covered_amount) }}
        </div>
    @endif

    <div>
        Qté {{ $item->quantity }} |
        @if($item->insurance_covered_amount > 0)
            {{ number_format($item->patient_amount) }} GNF
        @else
            {{ number_format($item->total_amount) }} GNF
        @endif
    </div>
</div>
@endforeach

<div class="separator"></div>

<div>Total Patient: {{ number_format($invoiceData['patient_amount']) }}</div>
<div>Payé: {{ number_format($invoiceData['paid_amount']) }}</div>

<div class="bold">
    Reste: {{ number_format($invoiceData['remaining']) }}
</div>

<div class="separator"></div>

<div class="center bold">
    @if($invoiceData['status'] === 'paid')
        PAYÉ
    @elseif($invoiceData['status'] === 'partial')
        PARTIEL
    @else
        NON PAYÉ
    @endif
</div>

</body>
</html>