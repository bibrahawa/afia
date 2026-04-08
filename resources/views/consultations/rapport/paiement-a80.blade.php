<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu de paiement</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 3mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            width: 74mm;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.35;
            color: #000;
        }

        .ticket {
            width: 74mm;
            margin: 0 auto;
        }

        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: 700; }
        .upper { text-transform: uppercase; }

        .logo {
            text-align: center;
            margin-bottom: 5px;
        }

        .logo img {
            max-width: 48mm;
            max-height: 42px;
            object-fit: contain;
        }

        .clinic-name {
            text-align: center;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
        }

        .clinic-line {
            text-align: center;
            font-size: 10px;
        }

        .title {
            text-align: center;
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 6px 0;
            margin: 8px 0;
        }

        .separator {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
            gap: 6px;
            margin: 3px 0;
        }

        .field {
            margin: 4px 0;
        }

        .field .label {
            font-weight: 700;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 7px 0;
        }

        th, td {
            padding: 4px 2px;
            font-size: 10.5px;
            border-bottom: 1px dashed #999;
        }

        th {
            text-transform: uppercase;
            text-align: left;
        }

        td:last-child,
        th:last-child {
            text-align: right;
        }

        .status {
            text-align: center;
            font-weight: 700;
            margin-top: 8px;
        }

        .footer {
            margin-top: 10px;
            text-align: center;
            font-size: 10px;
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

    $cashSources = ['ESPECE', 'ESPÈCE', 'CASH'];
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

<div class="ticket">
    <div class="logo">
        <img src="{{ public_path('assets/img/aprosafe.png') }}" alt="Logo">
    </div>

    <div class="clinic-name">{{ $hopital->name ?? "CLINIQUE APROSAFE" }}</div>
    <div class="clinic-line">{{ $hopital->address ?? 'Kiroti, Conakry, Rep de Guinee' }}</div>
    <div class="clinic-line">{{ $hopital->email ?? 'boubacarbinta2015@gmail.com' }} / {{ $hopital->contact ?? "628 16 44 22 / 625 47 68 44" }}</div>

    <div class="title">Reçu Paiement</div>

    <div class="meta-row">
        <span class="bold">Numéro reçu :</span>
        {{ $receiptNumber }}
    </div>

    <div class="meta-row">
        <span class="bold">Réf. facture :</span>
        {{ $invoiceRef }}
    </div>

    <div class="meta-row">
        <span class="bold">Date :</span>
        {{ ($lastPayment?->created_at ?? $consultation->created_at)->format('d/m/Y H:i') }}
    </div>

    <div class="separator"></div>

    <div class="field">
        <span class="label">Nom :</span>
        {{ $consultation->patient->last_name ?? '' }}
    </div>

    <div class="field">
        <span class="label">Prénoms :</span>
        {{ $consultation->patient->first_name ?? '' }}
    </div>

    <div class="field">
        <span class="label">Contact :</span>
        {{ $consultation->patient->phone ?? 'N/A' }}
    </div>

    <div class="field">
        <span class="label">Motif :</span>
        {{ $motif }}
    </div>

    <div class="separator"></div>

    <table>
        <thead>
            <tr>
                <th>Type paiement</th>
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
                <td class="bold">Total</td>
                <td class="bold">{{ number_format($totalPaid, 0, ',', ' ') }} GNF</td>
            </tr>
            <tr>
                <td class="bold">Solde</td>
                <td class="bold">{{ number_format($remaining, 0, ',', ' ') }} GNF</td>
            </tr>
        </tbody>
    </table>

    <div class="separator"></div>

    {{-- <div class="field">
        <span class="label">Mode paiement :</span>
        {{ $modePaiement }}
    </div> --}}

    <div class="field">
        <span class="label">Somme en lettre :</span><br>
        {{ $paymentAmountInWords }}
    </div>

    <div class="separator"></div>

    <div class="status">
        @if($status === 'paid')
            PAYÉ
        @elseif($status === 'partial')
            PAIEMENT PARTIEL
        @else
             NON PAYÉ
        @endif
    </div>

    <div class="footer">
        Merci pour votre confiance
    </div>
</div>
</body>
</html>