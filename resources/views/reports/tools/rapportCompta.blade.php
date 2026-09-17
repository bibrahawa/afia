@extends('layouts.backend')

@section('content')

{{-- ============================================================
     RAPPORT MENSUEL - APROSAFE CLINIQUE
     Inspiré du fichier Excel Paramétrage Aprosafe
     ============================================================ --}}

<style>
    @import url('https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap');

    :root {
        --apro-teal:       #0d7377;
        --apro-teal-light: #14a085;
        --apro-teal-pale:  #e8f7f6;
        --apro-gold:       #c8953a;
        --apro-gold-light: #f5e6cc;
        --apro-dark:       #1a2332;
        --apro-grey:       #6b7280;
        --apro-line:       #e2e8f0;
        --apro-white:      #ffffff;
        --apro-bg:         #f4f7f9;
        --radius:          10px;
        --shadow:          0 4px 24px rgba(13,115,119,.10);
    }

    .apro-wrap {
        font-family: 'DM Sans', sans-serif;
        background: var(--apro-bg);
        padding: 28px 20px 60px;
        min-height: 100vh;
    }

    /* ── Header card ── */
    .apro-header-card {
        background: var(--apro-dark);
        border-radius: var(--radius);
        padding: 28px 36px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 28px;
        box-shadow: var(--shadow);
        position: relative;
        overflow: hidden;
    }
    .apro-header-card::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(13,115,119,.35) 0%, transparent 60%);
        pointer-events: none;
    }
    .apro-header-card .brand {
        display: flex;
        align-items: center;
        gap: 14px;
        z-index: 1;
    }
    .apro-header-card .brand-icon {
        width: 52px; height: 52px;
        background: var(--apro-teal);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 22px; color: #fff;
        flex-shrink: 0;
    }
    .apro-header-card .brand-name {
        font-family: 'DM Serif Display', serif;
        font-size: 22px;
        color: #fff;
        line-height: 1.1;
        margin: 0;
    }
    .apro-header-card .brand-sub {
        font-size: 12px;
        color: rgba(255,255,255,.55);
        margin: 2px 0 0;
        letter-spacing: .6px;
        text-transform: uppercase;
    }
    .apro-header-card .period-badge {
        background: rgba(255,255,255,.08);
        border: 1px solid rgba(255,255,255,.15);
        border-radius: 40px;
        padding: 8px 20px;
        color: rgba(255,255,255,.85);
        font-size: 13px;
        font-weight: 500;
        z-index: 1;
    }
    .apro-header-card .period-badge span {
        color: var(--apro-teal-light);
        font-weight: 700;
    }
    .apro-header-card .action-btns {
        display: flex; gap: 10px; z-index: 1;
    }
    .btn-apro {
        padding: 8px 18px;
        border-radius: 7px;
        font-size: 13px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        transition: opacity .18s, transform .15s;
        text-decoration: none;
    }
    .btn-apro:hover { opacity: .88; transform: translateY(-1px); }
    .btn-apro-teal   { background: var(--apro-teal);  color: #fff; }
    .btn-apro-gold   { background: var(--apro-gold);  color: #fff; }
    .btn-apro-ghost  { background: rgba(255,255,255,.10); color: rgba(255,255,255,.8); border: 1px solid rgba(255,255,255,.18); }

    /* ── KPI cards ── */
    .kpi-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 28px;
    }
    .kpi-card {
        background: var(--apro-white);
        border-radius: var(--radius);
        padding: 20px 22px;
        box-shadow: var(--shadow);
        border-top: 3px solid transparent;
        transition: transform .2s;
    }
    .kpi-card:hover { transform: translateY(-3px); }
    .kpi-card.teal  { border-color: var(--apro-teal); }
    .kpi-card.gold  { border-color: var(--apro-gold); }
    .kpi-card.green { border-color: #22c55e; }
    .kpi-card.red   { border-color: #ef4444; }
    .kpi-card.indigo{ border-color: #6366f1; }
    .kpi-icon {
        width: 38px; height: 38px;
        border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        font-size: 16px;
        margin-bottom: 12px;
    }
    .kpi-icon.teal  { background: var(--apro-teal-pale);  color: var(--apro-teal); }
    .kpi-icon.gold  { background: var(--apro-gold-light); color: var(--apro-gold); }
    .kpi-icon.green { background: #dcfce7; color: #16a34a; }
    .kpi-icon.red   { background: #fee2e2; color: #dc2626; }
    .kpi-icon.indigo{ background: #e0e7ff; color: #4f46e5; }
    .kpi-label { font-size: 12px; color: var(--apro-grey); font-weight: 500; margin-bottom: 4px; text-transform: uppercase; letter-spacing: .5px; }
    .kpi-value { font-size: 22px; font-weight: 700; color: var(--apro-dark); line-height: 1; }
    .kpi-unit  { font-size: 11px; color: var(--apro-grey); font-weight: 400; margin-left: 3px; }

    /* ── Section titles ── */
    .section-title {
        font-family: 'DM Serif Display', serif;
        font-size: 18px;
        color: var(--apro-dark);
        margin: 0 0 14px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .section-title::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--apro-line);
    }

    /* ── Main table ── */
    .apro-table-card {
        background: var(--apro-white);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        overflow: hidden;
        margin-bottom: 28px;
    }
    .apro-table-card .card-head {
        padding: 16px 22px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid var(--apro-line);
        background: #fafbfc;
    }
    .apro-table-card .card-head h6 {
        margin: 0;
        font-weight: 600;
        font-size: 14px;
        color: var(--apro-dark);
    }
    .table-apro {
        width: 100%;
        border-collapse: collapse;
        font-size: 13.5px;
    }
    .table-apro thead tr {
        background: var(--apro-dark);
        color: #fff;
    }
    .table-apro thead th {
        padding: 13px 16px;
        font-weight: 600;
        font-size: 12px;
        letter-spacing: .5px;
        text-transform: uppercase;
        white-space: nowrap;
        border: none;
    }
    .table-apro tbody tr {
        border-bottom: 1px solid var(--apro-line);
        transition: background .12s;
    }
    .table-apro tbody tr:hover { background: var(--apro-teal-pale); }
    .table-apro tbody td {
        padding: 11px 16px;
        color: var(--apro-dark);
        vertical-align: middle;
    }
    .table-apro tfoot tr {
        background: var(--apro-gold-light);
        font-weight: 700;
    }
    .table-apro tfoot td {
        padding: 13px 16px;
        color: var(--apro-dark);
        font-size: 13px;
        border-top: 2px solid var(--apro-gold);
    }
    .badge-mode {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .badge-espece  { background: #dcfce7; color: #15803d; }
    .badge-pm      { background: #dbeafe; color: #1d4ed8; }
    .badge-tpe     { background: #ede9fe; color: #6d28d9; }
    .badge-assurance{ background: #fef9c3; color: #a16207; }
    .badge-chq     { background: #ffe4e6; color: #be123c; }

    .amount { font-variant-numeric: tabular-nums; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }

    /* ── Assurances + Doctors grid ── */
    .grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 28px;
    }
    @media (max-width: 768px) { .grid-2 { grid-template-columns: 1fr; } }

    .list-card {
        background: var(--apro-white);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        overflow: hidden;
    }
    .list-card .card-head {
        padding: 14px 20px;
        border-bottom: 1px solid var(--apro-line);
        background: #fafbfc;
    }
    .list-card .card-head h6 {
        margin: 0;
        font-weight: 600;
        font-size: 14px;
        color: var(--apro-dark);
    }
    .list-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 11px 20px;
        border-bottom: 1px solid var(--apro-line);
        transition: background .12s;
    }
    .list-item:last-child { border-bottom: none; }
    .list-item:hover { background: var(--apro-teal-pale); }
    .list-item .li-name {
        font-weight: 500;
        font-size: 13.5px;
        color: var(--apro-dark);
        display: flex;
        align-items: center;
        gap: 9px;
    }
    .li-avatar {
        width: 30px; height: 30px;
        border-radius: 50%;
        background: var(--apro-teal);
        color: #fff;
        font-size: 12px;
        font-weight: 700;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .li-avatar.gold { background: var(--apro-gold); }
    .list-item .li-count {
        font-size: 12px;
        color: var(--apro-grey);
        background: var(--apro-bg);
        padding: 2px 10px;
        border-radius: 20px;
        font-weight: 600;
    }
    .list-footer {
        padding: 12px 20px;
        background: var(--apro-dark);
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: rgba(255,255,255,.8);
        font-size: 13px;
        font-weight: 600;
    }
    .list-footer span { color: var(--apro-teal-light); }

    /* ── Mode de paiement summary ── */
    .payment-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 14px;
        margin-bottom: 28px;
    }
    .payment-card {
        background: var(--apro-white);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        padding: 18px 20px;
        display: flex;
        align-items: center;
        gap: 14px;
        transition: transform .18s;
    }
    .payment-card:hover { transform: translateY(-2px); }
    .pay-icon {
        width: 44px; height: 44px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }
    .pay-label { font-size: 11px; color: var(--apro-grey); font-weight: 600; text-transform: uppercase; margin-bottom: 3px; }
    .pay-value { font-size: 17px; font-weight: 700; color: var(--apro-dark); }

    /* ── Footer note ── */
    .apro-footer {
        text-align: center;
        color: var(--apro-grey);
        font-size: 12px;
        margin-top: 10px;
    }

    /* ── Print ── */
    @media print {
        .apro-header-card .action-btns,
        .btn-apro-ghost { display: none !important; }
        .apro-wrap { background: #fff; padding: 0; }
        .apro-header-card { background: #0d7377 !important; -webkit-print-color-adjust: exact; }
    }
</style>

@php
    /* ──────────────────────────────────────────────
     * Données dynamiques — remplace les valeurs par
     * tes variables Blade / contrôleur
     * ────────────────────────────────────────────── */

    $clinique     = $clinique_nom   ?? 'APROSAFE';
    $mois         = $mois_rapport   ?? 'Janvier 2026';
    $dateGenere   = now()->format('d/m/Y à H:i');

    // Détails des actes (feuille Détails1)
    $details = $details ?? [
        ['date'=>'29/01/2026','patient'=>'SYLLA OUMOU',           'service'=>'Consultation + Echographie',             'montant'=>60000,   'avance'=>0,   'reste'=>0,   'mode'=>'ESPECE','assurance'=>'NSIA'],
        ['date'=>'29/01/2026','patient'=>'KEITA FATOUMATA',        'service'=>'Consultation + Echo+Vaccin+Carnet',      'montant'=>700000,  'avance'=>0,   'reste'=>0,   'mode'=>'ESPECE','assurance'=>null],
        ['date'=>'29/01/2026','patient'=>'BARRY MARIAMA',          'service'=>'Consultation gyneco',                   'montant'=>250000,  'avance'=>0,   'reste'=>0,   'mode'=>'ESPECE','assurance'=>null],
        ['date'=>'29/01/2026','patient'=>'BAH HAWLATOU',           'service'=>'Consultation gyneco',                   'montant'=>50000,   'avance'=>0,   'reste'=>0,   'mode'=>'ESPECE','assurance'=>null],
        ['date'=>'29/01/2026','patient'=>'TOLNO MARIE',            'service'=>'Consultation + Echographie',             'montant'=>100000,  'avance'=>0,   'reste'=>0,   'mode'=>'ESPECE','assurance'=>'GS'],
        ['date'=>'29/01/2026','patient'=>'KABA SARATA',            'service'=>'Consultation gyneco',                   'montant'=>250000,  'avance'=>0,   'reste'=>0,   'mode'=>'ESPECE','assurance'=>null],
        ['date'=>'29/01/2026','patient'=>'DIALLO MARIAME',         'service'=>'Consultation gyneco',                   'montant'=>250000,  'avance'=>0,   'reste'=>0,   'mode'=>'ESPECE','assurance'=>null],
        ['date'=>'29/01/2026','patient'=>'DIALLO MARIAMA KESSO',   'service'=>'Hospitalisation+Médicaments',           'montant'=>1135000, 'avance'=>0,   'reste'=>0,   'mode'=>'ESPECE','assurance'=>null],
        ['date'=>'29/01/2026','patient'=>'BALDE FATIMA',           'service'=>'Echographie pelvienne',                 'montant'=>400000,  'avance'=>0,   'reste'=>0,   'mode'=>'PM',    'assurance'=>null],
        ['date'=>'29/01/2026','patient'=>'BARRY MARIAMA DJELO',    'service'=>'Consultation + Echographie',             'montant'=>190000,  'avance'=>0,   'reste'=>0,   'mode'=>'PM',    'assurance'=>'Cigna'],
        ['date'=>'29/01/2026','patient'=>'BALDE AISSATOU',         'service'=>'Consultation gyneco',                   'montant'=>550000,  'avance'=>0,   'reste'=>0,   'mode'=>'PM',    'assurance'=>null],
        ['date'=>'29/01/2026','patient'=>'BAH NENE OUMOU',         'service'=>'Médicaments+Soins+Observation',         'montant'=>955000,  'avance'=>0,   'reste'=>0,   'mode'=>'PM',    'assurance'=>null],
        ['date'=>'29/01/2026','patient'=>'BARRY DJENABOU',         'service'=>'Monitoring',                            'montant'=>500000,  'avance'=>0,   'reste'=>0,   'mode'=>'PM',    'assurance'=>null],
        ['date'=>'29/01/2026','patient'=>'BARRY FATOU',            'service'=>'Consultation gyneco',                   'montant'=>250000,  'avance'=>0,   'reste'=>0,   'mode'=>'TPE',   'assurance'=>null],
    ];

    // Actes récapitulatifs (feuille Situation Janvier)
    $actes = $actes ?? [
        ['service'=>'Consultation gyneco',                   'nb'=>9,  'espece'=>1600000, 'pm'=>1400000, 'tpe'=>250000, 'assurance'=>60000,  'total'=>3310000],
        ['service'=>'Consultation + Echographie',            'nb'=>3,  'espece'=>160000,  'pm'=>190000,  'tpe'=>0,      'assurance'=>100000, 'total'=>450000],
        ['service'=>'Echographie pelvienne',                 'nb'=>3,  'espece'=>400000,  'pm'=>800000,  'tpe'=>0,      'assurance'=>0,      'total'=>1200000],
        ['service'=>'Hospitalisation+Médicaments',           'nb'=>1,  'espece'=>1135000, 'pm'=>0,       'tpe'=>0,      'assurance'=>0,      'total'=>1135000],
        ['service'=>'Consultation + Echo+Vaccin+Carnet',     'nb'=>1,  'espece'=>700000,  'pm'=>0,       'tpe'=>0,      'assurance'=>0,      'total'=>700000],
        ['service'=>'Médicaments+Soins+Observation',         'nb'=>1,  'espece'=>0,       'pm'=>955000,  'tpe'=>0,      'assurance'=>0,      'total'=>955000],
        ['service'=>'Monitoring',                            'nb'=>1,  'espece'=>0,       'pm'=>500000,  'tpe'=>0,      'assurance'=>0,      'total'=>500000],
        ['service'=>'Médicaments+Soins',                     'nb'=>2,  'espece'=>100000,  'pm'=>200000,  'tpe'=>0,      'assurance'=>0,      'total'=>300000],
        ['service'=>'Balde Aissatou (Consultation)',         'nb'=>1,  'espece'=>0,       'pm'=>550000,  'tpe'=>0,      'assurance'=>0,      'total'=>550000],
    ];

    $totalEspece    = array_sum(array_column($actes,'espece'));
    $totalPM        = array_sum(array_column($actes,'pm'));
    $totalTPE       = array_sum(array_column($actes,'tpe'));
    $totalAssurance = array_sum(array_column($actes,'assurance'));
    $grandTotal     = array_sum(array_column($actes,'total'));
    $totalPatients  = count($details);
    $totalActes     = array_sum(array_column($actes,'nb'));

    // Assurances
    $assurances = $assurances ?? [
        'NSIA'=>1,'Cigna'=>1,'GS'=>1,
    ];

    // Docteurs
    $docteurs = $docteurs ?? [
        'Docteur Binta'         => 'Gynécologie',
        'Docteur Alpha'         => 'Pédiatrie',
        'Docteur Kalo'          => 'Gynécologie',
        'Docteur Hadja Mariama' => 'Gynécologie',
        'Docteur Diego'         => 'Chirurgie',
    ];

    // Modes de paiement
    $modes = [
        'ESPECE'    => ['icon'=>'fa-money-bill-wave','color'=>'#22c55e','bg'=>'#dcfce7', 'total'=>$totalEspece],
        'PM'        => ['icon'=>'fa-mobile-alt',     'color'=>'#3b82f6','bg'=>'#dbeafe', 'total'=>$totalPM],
        'TPE'       => ['icon'=>'fa-credit-card',    'color'=>'#8b5cf6','bg'=>'#ede9fe', 'total'=>$totalTPE],
        'Assurance' => ['icon'=>'fa-shield-alt',     'color'=>'#f59e0b','bg'=>'#fef3c7', 'total'=>$totalAssurance],
    ];
@endphp

<div class="apro-wrap" id="printableArea">

    {{-- ── HEADER ── --}}
    <div class="apro-header-card">
        <div class="brand">
            <div class="brand-icon"><i class="fa fa-heartbeat"></i></div>
            <div>
                <p class="brand-name">{{ $clinique }}</p>
                <p class="brand-sub">{{ \App\Support\Etablissement\IdentiteDocument::courante()->coordonnees() }}</p>
            </div>
        </div>

        <div class="period-badge">
            Rapport mensuel &mdash; <span>{{ $mois }}</span>
        </div>

        <div class="action-btns">
            <a href="#" class="btn-apro btn-apro-teal" onclick="exportToExcel()">
                <i class="fa fa-file-excel"></i> Excel
            </a>
            <a href="#" class="btn-apro btn-apro-gold" onclick="window.print()">
                <i class="fa fa-print"></i> Imprimer
            </a>
            <a href="{{ url()->previous() }}" class="btn-apro btn-apro-ghost">
                <i class="fa fa-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    {{-- ── KPI ── --}}
    <div class="kpi-row">
        <div class="kpi-card teal">
            <div class="kpi-icon teal"><i class="fa fa-users"></i></div>
            <div class="kpi-label">Patients traités</div>
            <div class="kpi-value">{{ $totalPatients }}<span class="kpi-unit">pts</span></div>
        </div>
        <div class="kpi-card gold">
            <div class="kpi-icon gold"><i class="fa fa-stethoscope"></i></div>
            <div class="kpi-label">Actes réalisés</div>
            <div class="kpi-value">{{ $totalActes }}<span class="kpi-unit">actes</span></div>
        </div>
        <div class="kpi-card green">
            <div class="kpi-icon green"><i class="fa fa-money-bill-wave"></i></div>
            <div class="kpi-label">Total Espèces</div>
            <div class="kpi-value amount">{{ number_format($totalEspece,0,',',' ') }}<span class="kpi-unit">GNF</span></div>
        </div>
        <div class="kpi-card red">
            <div class="kpi-icon red"><i class="fa fa-mobile-alt"></i></div>
            <div class="kpi-label">Total PM</div>
            <div class="kpi-value amount">{{ number_format($totalPM,0,',',' ') }}<span class="kpi-unit">GNF</span></div>
        </div>
        <div class="kpi-card indigo">
            <div class="kpi-icon indigo"><i class="fa fa-chart-bar"></i></div>
            <div class="kpi-label">Total Général</div>
            <div class="kpi-value amount">{{ number_format($grandTotal,0,',',' ') }}<span class="kpi-unit">GNF</span></div>
        </div>
    </div>

    {{-- ── MODES DE PAIEMENT SUMMARY ── --}}
    <p class="section-title"><i class="fa fa-wallet" style="color:var(--apro-teal)"></i> Répartition par mode de paiement</p>
    <div class="payment-row">
        @foreach($modes as $label => $info)
        <div class="payment-card">
            <div class="pay-icon" style="background:{{ $info['bg'] }};color:{{ $info['color'] }}">
                <i class="fa {{ $info['icon'] }}"></i>
            </div>
            <div>
                <div class="pay-label">{{ $label }}</div>
                <div class="pay-value amount">{{ number_format($info['total'],0,',',' ') }} <small style="font-size:10px;font-weight:400">GNF</small></div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ── SITUATION MENSUELLE PAR ACTE ── --}}
    <p class="section-title"><i class="fa fa-table" style="color:var(--apro-teal)"></i> Situation mensuelle par service</p>
    <div class="apro-table-card">
        <div class="card-head">
            <h6><i class="fa fa-list-alt me-2" style="color:var(--apro-teal)"></i>Récapitulatif des actes — {{ $mois }}</h6>
            <small style="color:var(--apro-grey)">Paiements reçus : Espèce / PM / TPE / Assurance</small>
        </div>
        <div style="overflow-x:auto">
            <table class="table-apro">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Service / Acte</th>
                        <th class="text-center">Nb actes</th>
                        <th class="text-right">Espèce</th>
                        <th class="text-right">PM</th>
                        <th class="text-right">TPE</th>
                        <th class="text-right">Assurance</th>
                        <th class="text-right">Total général</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($actes as $i => $a)
                    <tr>
                        <td style="color:var(--apro-grey);font-size:12px">{{ $i+1 }}</td>
                        <td style="font-weight:500">{{ $a['service'] }}</td>
                        <td class="text-center">{{ $a['nb'] }}</td>
                        <td class="text-right amount">{{ $a['espece'] > 0 ? number_format($a['espece'],0,',',' ').' GNF' : '—' }}</td>
                        <td class="text-right amount">{{ $a['pm']     > 0 ? number_format($a['pm'],    0,',',' ').' GNF' : '—' }}</td>
                        <td class="text-right amount">{{ $a['tpe']    > 0 ? number_format($a['tpe'],   0,',',' ').' GNF' : '—' }}</td>
                        <td class="text-right amount">{{ $a['assurance'] > 0 ? number_format($a['assurance'],0,',',' ').' GNF' : '—' }}</td>
                        <td class="text-right amount" style="font-weight:700;color:var(--apro-teal)">{{ number_format($a['total'],0,',',' ') }} GNF</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="text-right" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px">Totaux</td>
                        <td class="text-center">{{ $totalActes }}</td>
                        <td class="text-right amount">{{ number_format($totalEspece,   0,',',' ') }} GNF</td>
                        <td class="text-right amount">{{ number_format($totalPM,       0,',',' ') }} GNF</td>
                        <td class="text-right amount">{{ number_format($totalTPE,      0,',',' ') }} GNF</td>
                        <td class="text-right amount">{{ number_format($totalAssurance,0,',',' ') }} GNF</td>
                        <td class="text-right amount" style="color:var(--apro-gold)">{{ number_format($grandTotal,0,',',' ') }} GNF</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- ── DÉTAILS PATIENTS ── --}}
    <p class="section-title"><i class="fa fa-file-medical-alt" style="color:var(--apro-teal)"></i> Détails des consultations</p>
    <div class="apro-table-card" style="margin-bottom:28px">
        <div class="card-head">
            <h6><i class="fa fa-user-injured me-2" style="color:var(--apro-teal)"></i>Liste des patients — {{ $mois }}</h6>
            <small style="color:var(--apro-grey)">{{ $totalPatients }} entrées</small>
        </div>
        <div style="overflow-x:auto">
            <table class="table-apro">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Patient</th>
                        <th>Service</th>
                        <th class="text-right">Montant</th>
                        <th class="text-right">Avance</th>
                        <th class="text-right">Reste</th>
                        <th class="text-center">Mode</th>
                        <th class="text-center">Assurance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($details as $d)
                    <tr>
                        <td style="font-size:12px;color:var(--apro-grey);white-space:nowrap">{{ $d['date'] }}</td>
                        <td style="font-weight:600">{{ $d['patient'] }}</td>
                        <td style="font-size:13px">{{ $d['service'] }}</td>
                        <td class="text-right amount" style="font-weight:600">{{ number_format($d['montant'],0,',',' ') }} GNF</td>
                        <td class="text-right amount">{{ $d['avance'] > 0 ? number_format($d['avance'],0,',',' ').' GNF' : '—' }}</td>
                        <td class="text-right amount" style="{{ $d['reste'] > 0 ? 'color:#ef4444;font-weight:600' : '' }}">
                            {{ $d['reste'] > 0 ? number_format($d['reste'],0,',',' ').' GNF' : '—' }}
                        </td>
                        <td class="text-center">
                            @php $m = strtolower($d['mode']); @endphp
                            <span class="badge-mode badge-{{ $m }}">{{ $d['mode'] }}</span>
                        </td>
                        <td class="text-center">
                            @if($d['assurance'])
                                <span class="badge-mode badge-assurance">{{ $d['assurance'] }}</span>
                            @else
                                <span style="color:var(--apro-line)">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── ASSURANCES + DOCTEURS ── --}}
    <div class="grid-2">

        {{-- Assurances --}}
        <div>
            <p class="section-title"><i class="fa fa-shield-alt" style="color:var(--apro-teal)"></i> Assurances partenaires</p>
            <div class="list-card">
                <div class="card-head"><h6><i class="fa fa-handshake me-2" style="color:var(--apro-gold)"></i>Consultations par assurance</h6></div>
                @php
                    $allAssurances = ['ASCOMA','Cigna','Dayo','EC','GS','Lanala','NSIA','OLEA','SAAR','SUNU','UGAR','VISTA'];
                    $totalConsult  = array_sum($assurances);
                @endphp
                @foreach($allAssurances as $ass)
                <div class="list-item">
                    <div class="li-name">
                        <div class="li-avatar">{{ strtoupper(substr($ass,0,2)) }}</div>
                        {{ $ass }}
                    </div>
                    <span class="li-count">{{ $assurances[$ass] ?? 0 }} consult.</span>
                </div>
                @endforeach
                <div class="list-footer">
                    Total consultations assurées <span>{{ $totalConsult }}</span>
                </div>
            </div>
        </div>

        {{-- Docteurs --}}
        <div>
            <p class="section-title"><i class="fa fa-user-md" style="color:var(--apro-teal)"></i> Équipe médicale</p>
            <div class="list-card">
                <div class="card-head"><h6><i class="fa fa-stethoscope me-2" style="color:var(--apro-gold)"></i>Médecins actifs — {{ $mois }}</h6></div>
                @foreach($docteurs as $nom => $specialite)
                <div class="list-item">
                    <div class="li-name">
                        <div class="li-avatar gold">{{ strtoupper(substr(explode(' ',$nom)[count(explode(' ',$nom))-1],0,2)) }}</div>
                        <div>
                            <div style="font-weight:600;font-size:13.5px">{{ $nom }}</div>
                            <div style="font-size:11px;color:var(--apro-grey)">{{ $specialite }}</div>
                        </div>
                    </div>
                    <span class="li-count" style="background:var(--apro-gold-light);color:var(--apro-gold)">Actif</span>
                </div>
                @endforeach
                <div class="list-footer">
                    Total médecins <span>{{ count($docteurs) }}</span>
                </div>
            </div>
        </div>

    </div>

    {{-- ── FOOTER ── --}}
    <div class="apro-footer">
        <i class="fa fa-clock me-1"></i>Rapport généré le <strong>{{ $dateGenere }}</strong> &mdash; {{ $clinique }}
    </div>

</div>{{-- /apro-wrap --}}

@endsection

{{-- ── Scripts ── --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
function exportToExcel() {
    const wb    = XLSX.utils.book_new();
    const mois  = @json($mois  ?? 'Janvier 2026');
    const clini = @json($clinique ?? 'APROSAFE');

    // ── Feuille 1 : Situation mensuelle ──
    const sit = [
        [clini + ' — Rapport mensuel'],
        ['Période : ' + mois],
        [],
        ['Service','Nb actes','Espèce','PM','TPE','Assurance','Total général'],
        ...@json($actes ?? []).map(a => [
            a.service, a.nb,
            a.espece    > 0 ? a.espece    + ' GNF' : '-',
            a.pm        > 0 ? a.pm        + ' GNF' : '-',
            a.tpe       > 0 ? a.tpe       + ' GNF' : '-',
            a.assurance > 0 ? a.assurance + ' GNF' : '-',
            a.total     > 0 ? a.total     + ' GNF' : '-',
        ]),
        [],
        ['TOTAUX','',
            '{{ number_format($totalEspece,0,",","_") }} GNF',
            '{{ number_format($totalPM,0,",","_") }} GNF',
            '{{ number_format($totalTPE,0,",","_") }} GNF',
            '{{ number_format($totalAssurance,0,",","_") }} GNF',
            '{{ number_format($grandTotal,0,",","_") }} GNF',
        ],
    ];
    const wsSit = XLSX.utils.aoa_to_sheet(sit);
    wsSit['!cols'] = [{wch:40},{wch:10},{wch:16},{wch:16},{wch:16},{wch:16},{wch:18}];
    XLSX.utils.book_append_sheet(wb, wsSit, 'Situation '+mois);

    // ── Feuille 2 : Détails ──
    const det = [
        ['Date','Patient','Service','Montant','Avance','Reste','Mode','Assurance'],
        ...@json($details ?? []).map(d => [
            d.date, d.patient, d.service,
            d.montant+' GNF',
            d.avance > 0 ? d.avance+' GNF' : '-',
            d.reste  > 0 ? d.reste +' GNF' : '-',
            d.mode, d.assurance || '-'
        ]),
    ];
    const wsDet = XLSX.utils.aoa_to_sheet(det);
    wsDet['!cols'] = [{wch:13},{wch:25},{wch:38},{wch:16},{wch:14},{wch:14},{wch:10},{wch:12}];
    XLSX.utils.book_append_sheet(wb, wsDet, 'Détails');

    XLSX.writeFile(wb, 'rapport_' + clini + '_' + mois.replace(/ /g,'-') + '.xlsx');
}
</script>