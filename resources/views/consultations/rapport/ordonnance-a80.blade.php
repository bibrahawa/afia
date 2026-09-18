<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ordonnance A80</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 3mm;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 0;
            width: 74mm;
            font-family: Arial, Helvetica, sans-serif;
            background: #fff;
            color: #000;
            font-size: 11px;
            line-height: 1.4;
        }

        .ticket {
            width: 74mm;
            margin: 0 auto;
        }

        .center { text-align: center; }
        .bold { font-weight: 700; }

        .logo {
            text-align: center;
            margin-bottom: 6px;
        }

        .logo img {
            max-width: 50mm;
            max-height: 45px;
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

        .line {
            margin: 3px 0;
        }

        .separator {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }

        .item {
            margin-bottom: 10px;
        }

        .item-name {
            font-weight: 700;
        }

        .footer {
            margin-top: 18px;
            text-align: right;
        }

        .signature {
            margin-top: 25px;
            font-weight: 700;
        }

        .print-btn {
            margin: 10px auto;
            display: block;
            border: none;
            background: #111;
            color: #fff;
            padding: 8px 14px;
            cursor: pointer;
            border-radius: 4px;
        }

        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body onload="window.print()">
<div class="ticket">
    <div class="no-print">
        <button class="print-btn" onclick="window.print()">Imprimer</button>
    </div>

    <div class="logo">
        @if($identite->logoWeb())<img src="{{ $identite->logoWeb() }}" alt="Logo">@endif
    </div>

    <div class="clinic-name">{{ $identite->nom }}</div>
    <div class="clinic-line">{{ $identite->adresse }}</div>
    <div class="clinic-line">Tél : {{ $identite->contact }}</div>

    <div class="title">Ordonnance</div>

    <div class="line"><span class="bold">Date :</span> {{ $consultation->created_at->format('d/m/Y') }}</div>
    <div class="line"><span class="bold">Patient :</span> {{ $consultation->patient->first_name }} {{ $consultation->patient->last_name }}</div>
    {{-- <div class="line"><span class="bold">Médecin :</span> Dr. {{ $consultation->medecin->first_name }} {{ $consultation->medecin->last_name }}</div> --}}
    <div class="line"><span class="bold">Service :</span> {{ $consultation->department->name }}</div>

    <div class="separator"></div>

    @forelse($consultation->medicaments as $index => $med)
        <div class="item">
            <div class="item-name">{{ $index + 1 }}. {{ $med->nom }}
                @if($med->pivot->dose ?: $med->dosage) {{ $med->pivot->dose ?: $med->dosage }} @endif
            </div>
            <div>{{ $med->pivot->frequence ?: $med->frequence }} @if($med->pivot->duree ?: $med->duree)- {{ $med->pivot->duree ?: $med->duree }}@endif</div>
            @if($med->pivot->instructions ?: $med->instructions)
                <div><em>{{ $med->pivot->instructions ?: $med->instructions }}</em></div>
            @endif
        </div>
    @empty
        <div class="center" style="padding: 20px 0;">Aucun médicament prescrit.</div>
    @endforelse

    <div class="separator"></div>

    <div class="footer">
        <div>{{ $identite->nom }}</div>
        {{-- <div class="signature">Dr. {{ $consultation->medecin->first_name }}</div> --}}
    </div>
</div>
</body>
</html>