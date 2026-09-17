<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Facture A5</title>

<style>
@page { size: A5 portrait; margin: 10mm; }

body {
    font-family: "Times New Roman", serif;
    font-size: 13px;
}

.header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
}

.title {
    text-align: center;
    font-size: 20px;
    font-weight: bold;
    text-decoration: underline;
}

.meta {
    border: 1px solid #333;
    padding: 10px;
    margin: 15px 0;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    border: 1px solid #ddd;
    padding: 8px;
}

th {
    background: #f5f5f5;
}

.total {
    margin-top: 20px;
    float: right;
    width: 250px;
}

.total div {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
}

.bold { font-weight: bold; }

.status {
    margin-top: 10px;
    font-weight: bold;
}
.logo img {
        max-width: 130px;
        max-height: 70px;
        object-fit: contain;
    }
</style>
</head>

<body onload="window.print()">

<div class="header">
    <div>
        <div class="logo">
            @if($identite->logoPdf())<img src="{{ $identite->logoPdf() }}" alt="Logo">@endif
        </div>
        <strong>{{ $identite->nom }}</strong><br>
        {{ $identite->adresse }}<br>
        {{ $identite->contact }}
    </div>

    <div>
        <strong>Facture N°</strong><br>
        {{ str_pad($consultation->id,6,'0',STR_PAD_LEFT) }}
    </div>
</div>

<div class="title">FACTURE</div>

<div class="meta">
    Patient : {{ $consultation->patient->first_name }} {{ $consultation->patient->last_name }}<br>
    {{-- Médecin : Dr. {{ $consultation->medecin->first_name }}<br> --}}
    Date : {{ $consultation->created_at->format('d/m/Y') }}
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Description</th>
            <th>Qté</th>
            <th>Prix Patient</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoiceData['invoice']->items as $index => $item)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>
                {{ $item->description }}

                @if($item->insurance_covered_amount > 0)
                    <br>
                    <small style="color:green">
                        Assurance: -{{ number_format($item->insurance_covered_amount) }} GNF
                    </small>
                @endif
            </td>
            <td>{{ $item->quantity }}</td>
            @if($item->insurance_covered_amount > 0)
                <td>{{ number_format($item->patient_amount) }} GNF</td>
            @else
             <td>{{ number_format($item->total_amount) }} GNF</td>
            @endif
        </tr>
        @endforeach
    </tbody>
</table>

<div class="total">
    <div>
        <span>Total Patient :</span>
        <span>{{ number_format($invoiceData['patient_amount']) }} GNF</span>
    </div>

    <div>
        <span>Payé :</span>
        <span>{{ number_format($invoiceData['paid_amount']) }} GNF</span>
    </div>

    <div class="bold">
        <span>Reste :</span>
        <span>{{ number_format($invoiceData['remaining']) }} GNF</span>
    </div>
</div>

<div class="status">
    @if($invoiceData['status'] === 'paid')
        Facture payée
    @elseif($invoiceData['status'] === 'partial')
        Paiement partiel
    @else
        Non payée
    @endif
</div>

</body>
</html>