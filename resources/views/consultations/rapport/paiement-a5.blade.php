<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu de paiement A5</title>
    <style>
        @page {
            size: A5 portrait;
            margin: 10mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: "Times New Roman", serif;
            font-size: 13px;
            color: #000;
        }

        .left {
            float: left;
            width: 50%;
        }

        .right {
            float: right;
            width: 50%;
            text-align: right;
        }

        .logo img {
            max-width: 130px;
            max-height: 70px;
            object-fit: contain;
        }

        .clinic-name {
            font-size: 18px;
            font-weight: 700;
            text-transform: uppercase;
            margin-top: 6px;
        }

        .clinic-line {
            font-size: 12px;
            line-height: 1.5;
        }

        .title {
            text-align: center;
            font-size: 22px;
            font-weight: 700;
            text-transform: uppercase;
            text-decoration: underline;
            margin-top: 10px;
        }

        .meta-box,
        .info-box,
        .words-box {
            border: 1px solid #333;
            padding: 10px 12px;
            margin-top: 15px;
        }

        .meta-box,
        .info-box {
            line-height: 1.7;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }

        th, td {
            border: 1px solid #333;
            padding: 8px 6px;
        }

        th {
            background: #efefef;
            text-transform: uppercase;
        }

        td:last-child,
        th:last-child {
            text-align: right;
        }

        .status {
            margin-top: 16px;
            text-align: right;
            font-weight: 700;
        }
    </style>
</head>
<body>
@php
    $transaction = $consultation->transaction;
    $invoice = optional($transaction)->invoice;
    $paiements = $transaction?->paiements ?? collect();

    $normalize = function ($value) {
        return strtoupper(trim((string) $value));
    };

    $cashSources = ['ESPECE', 'ESPÈCE', 'CASH', 'LIQUIDE'];
    $mobileSources = ['MOBILE', 'MOBILE MONEY', 'OM', 'ORANGE MONEY', 'WAVE', 'MTN MONEY'];
    $cardSources = ['CARTE', 'CARD', 'CB', 'TPE'];

    $cashAmount = $paiements->filter(fn($p) => in_array($normalize($p->source ?? ''), $cashSources))->sum('montant');
    $mobileAmount = $paiements->filter(fn($p) => in_array($normalize($p->source ?? ''), $mobileSources))->sum('montant');
    $cardAmount = $paiements->filter(fn($p) => in_array($normalize($p->source ?? ''), $cardSources))->sum('montant');

    $totalPaid = $invoiceData['paid_amount'] ?? 0;
    $patientAmount = $invoiceData['patient_amount'] ?? 0;
    $remaining = $invoiceData['remaining'] ?? 0;
    $status = $invoiceData['status'] ?? 'unpaid';

    $lastPayment = $paiements->sortByDesc('created_at')->first();
    $modePaiement = $lastPayment->source ?? 'Non précisé';

    $receiptNumber = $receiptNumber ?? ('REC-' . now()->format('Y') . str_pad($consultation->id, 5, '0', STR_PAD_LEFT));
    $invoiceRef = $invoiceRef ?? ($invoice->invoice_no ?? ('FAC-' . str_pad($consultation->id, 5, '0', STR_PAD_LEFT)));
    $paymentAmountInWords = $paymentAmountInWords ?? 'À compléter';

    $motif = $transaction->description ?? $consultation->motif ?? 'Paiement consultation';
@endphp

<table style="width:100%; margin-bottom:20px;">
    <tr>
        <!-- LEFT -->
        <td style="width:50%; vertical-align:top;">
            <div class="logo">
                <img src="{{ public_path('assets/img/aprosafe.png') }}" alt="Logo">
            </div>

            <div class="clinic-name">{{ $hopital->name ?? "CLINIQUE APROSAFE" }}</div>
            <div class="clinic-line">{{ $hopital->address ?? 'Kiroti, Conakry, Rep de Guinee' }}</div>
            <div class="clinic-line">{{ $hopital->email ?? 'boubacarbinta2015@gmail.com' }}</div>
            <div class="clinic-line">{{ $hopital->contact ?? "628 16 44 22 / 625 47 68 44" }}</div>
 
        </td>

        <!-- RIGHT -->
        <td style="width:50%; vertical-align:top; text-align:right;">
            <div class="title">Reçu de paiement</div>

            <div class="meta-box" style="display:inline-block; text-align:left;">
                <strong>Numéro reçu :</strong> {{ $receiptNumber }}<br>
                <strong>Référence facture :</strong> {{ $invoiceRef }}<br>
                <strong>Date :</strong> {{ ($lastPayment?->created_at ?? $consultation->created_at)->format('d/m/Y H:i') }}
            </div>
        </td>
    </tr>
</table>

<div class="info-box">
    <strong>Nom :</strong> {{ $consultation->patient->last_name ?? '' }}<br>
    <strong>Prénoms :</strong> {{ $consultation->patient->first_name ?? '' }}<br>
    <strong>Contact :</strong> {{ $consultation->patient->phone ?? 'N/A' }}<br>
    <strong>Motif :</strong> {{ $motif }}
</div>

<table>
    <thead>
        <tr>
            <th>Type montant</th>
            <th>Valeur</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Espèce</td>
            <td>{{ number_format($cashAmount, 0, ',', ' ') }} GNF</td>
        </tr>
        <tr>
            <td>Mobile</td>
            <td>{{ number_format($mobileAmount, 0, ',', ' ') }} GNF</td>
        </tr>
        <tr>
            <td>Carte</td>
            <td>{{ number_format($cardAmount, 0, ',', ' ') }} GNF</td>
        </tr>
        <tr>
            <td><strong>Total</strong></td>
            <td><strong>{{ number_format($totalPaid, 0, ',', ' ') }} GNF</strong></td>
        </tr>
        <tr>
            <td><strong>Solde</strong></td>
            <td><strong>{{ number_format($remaining, 0, ',', ' ') }} GNF</strong></td>
        </tr>
    </tbody>
</table>

{{-- <div class="info-box">
    <strong>Mode paiement :</strong> {{ $modePaiement }}
</div> --}}

<div class="words-box">
    <strong>Somme en lettre :</strong><br>
    {{ $paymentAmountInWords }}
</div>
</body>
</html>