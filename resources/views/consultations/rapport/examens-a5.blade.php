<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Examens prescrits A5</title>
    <style>
        @page {
            size: A5 portrait;
            margin: 10mm;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Times New Roman", serif;
            color: #000;
            background: #fff;
            font-size: 13px;
        }

        .page {
            width: 100%;
        }

        .header {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 20px;
        }

        .left, .right {
            width: 48%;
        }

        .logo img {
            max-width: 120px;
            max-height: 70px;
            object-fit: contain;
        }

        .clinic-name {
            font-size: 17px;
            font-weight: 700;
            text-transform: uppercase;
            margin-top: 6px;
        }

        .clinic-line {
            line-height: 1.5;
            font-size: 12px;
        }

        .title {
            text-align: center;
            font-size: 22px;
            font-weight: 700;
            text-transform: uppercase;
            text-decoration: underline;
            margin-top: 15px;
        }

        .date {
            text-align: center;
            margin-top: 10px;
            font-size: 13px;
            font-weight: 700;
        }

        .meta-box {
            border: 1px solid #333;
            padding: 10px 12px;
            margin: 18px 0 22px;
            font-size: 13px;
            line-height: 1.7;
        }

        .section-title {
            margin-top: 18px;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 15px;
        }

        .exam-list {
            margin-top: 15px;
            min-height: 280px;
        }

        .exam-item {
            margin-bottom: 12px;
            font-size: 15px;
        }

        .instructions {
            margin-top: 20px;
            border: 1px solid #333;
            padding: 12px;
            min-height: 70px;
        }

        .footer {
            margin-top: auto;
            text-align: right;
        }

        .signature {
            margin-top: 45px;
            font-weight: 700;
        }

        .print-btn {
            margin-bottom: 12px;
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
    <div class="page">
        <div class="no-print">
            <button class="print-btn" onclick="window.print()">Imprimer</button>
        </div>

        <div class="header">
            <div class="left">
                <div class="logo">
                    <img src="{{ asset($hopital->logo ?? 'assets/img/aprosafe.png') }}" alt="Logo">
                </div>

                <div class="clinic-name">{{ $hopital->name ?? "CLINIQUE APROSAFE" }}</div>
                <div class="clinic-line">{{ $hopital->address ?? 'Kiroti, Conakry, Rep de Guinee' }}</div>
                <div class="clinic-line">Tél : {{ $hopital->contact ?? "628 16 44 22 / 625 47 68 44" }}</div>
                <div class="clinic-line">Email : {{ $hopital->email ?? 'boubacarbinta2015@gmail.com'    }}</div>
            </div>

            <div class="right">
                <div class="title">Demande d'examens</div>
                <div class="date">
                    {{ $consultation->created_at->translatedFormat('d F Y') }}
                </div>
            </div>
        </div>

        <div class="meta-box">
            <strong>Patient :</strong> {{ $consultation->patient->first_name }} {{ $consultation->patient->last_name }}<br>
            <strong>Téléphone :</strong> {{ $consultation->patient->phone }}<br>
            {{-- <strong>Médecin :</strong> Dr. {{ $consultation->medecin->first_name }} {{ $consultation->medecin->last_name }}<br> --}}
            <strong>Diagnostic :</strong> {{ $consultation->diagnostic ?? 'En cours' }}
        </div>

        <div><strong>Motif :</strong> {{ $consultation->motif ?? 'Non précisé' }}</div>

        <div class="section-title">Examens demandés</div>

        <div class="exam-list">
            @forelse($consultation->tests as $index => $test)
                <div class="exam-item">
                    {{ $index + 1 }}. <strong>{{ $test->name }}</strong>
                </div>
            @empty
                <p style="text-align:center; margin-top:90px;">Aucun examen demandé.</p>
            @endforelse
        </div>

        @if($consultation->observation)
            <div class="section-title">Instructions</div>
            <div class="instructions">
                {{ $consultation->observation }}
            </div>
        @endif
    </div>
</body>
</html>