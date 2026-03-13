@extends('layouts.backend')

@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap');

    :root {
        --teal:       #0d7377;
        --teal-light: #14a085;
        --teal-pale:  #e8f7f6;
        --gold:       #c8953a;
        --gold-light: #f5e6cc;
        --dark:       #1a2332;
        --grey:       #6b7280;
        --line:       #e2e8f0;
        --bg:         #f4f7f9;
        --white:      #ffffff;
        --radius:     10px;
        --shadow:     0 4px 24px rgba(13,115,119,.10);
    }

    .wrap { font-family:'DM Sans',sans-serif; background:var(--bg); padding:28px 20px 60px; min-height:100vh; }
    .hdr {
        background:var(--dark); border-radius:var(--radius);
        padding:24px 32px; display:flex; align-items:center;
        justify-content:space-between; flex-wrap:wrap; gap:14px;
        margin-bottom:24px; box-shadow:var(--shadow); position:relative; overflow:hidden;
    }
    .hdr::before { content:''; position:absolute; inset:0; background:linear-gradient(135deg,rgba(13,115,119,.35) 0%,transparent 60%); pointer-events:none; }
    .hdr .brand  { display:flex; align-items:center; gap:12px; z-index:1; }
    .hdr .b-icon { width:48px;height:48px;background:var(--teal);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;color:#fff;flex-shrink:0; }
    .hdr .b-name { font-family:'DM Serif Display',serif;font-size:20px;color:#fff;margin:0;line-height:1.1; }
    .hdr .b-sub  { font-size:11px;color:rgba(255,255,255,.5);margin:2px 0 0;letter-spacing:.6px;text-transform:uppercase; }
    .hdr .period { background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);border-radius:40px;padding:7px 18px;color:rgba(255,255,255,.8);font-size:13px;font-weight:500;z-index:1; }
    .hdr .period strong { color:var(--teal-light); }
    .hdr .btns   { display:flex;gap:10px;z-index:1; }
    .btn-a { padding:8px 16px;border-radius:7px;font-size:13px;font-weight:600;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:7px;text-decoration:none; }
    .btn-teal  { background:var(--teal);color:#fff; }
    .btn-gold  { background:var(--gold);color:#fff; }
    .btn-ghost { background:rgba(255,255,255,.10);color:rgba(255,255,255,.8);border:1px solid rgba(255,255,255,.18); }

    .filter-card {
        background:var(--white);border-radius:var(--radius);padding:18px 24px;
        box-shadow:var(--shadow);display:flex;align-items:flex-end;gap:16px;
        flex-wrap:wrap;margin-bottom:24px;
    }
    .filter-card label { font-size:12px;font-weight:600;color:var(--grey);text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:5px; }
    .filter-card input[type="date"] {
        padding:8px 12px;border:1px solid var(--line);border-radius:7px;
        font-size:13px;color:var(--dark);font-family:'DM Sans',sans-serif;background:var(--bg);outline:none;
    }
    .filter-card .btn-filter {
        padding:9px 20px;background:var(--teal);color:#fff;border:none;border-radius:7px;
        font-size:13px;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;
    }

    .kpi-row { display:grid;grid-template-columns:repeat(auto-fit,minmax(175px,1fr));gap:14px;margin-bottom:24px; }
    .kpi { background:var(--white);border-radius:var(--radius);padding:18px 20px;box-shadow:var(--shadow);border-top:3px solid transparent; }
    .kpi.t1{border-color:var(--teal);} .kpi.t2{border-color:var(--gold);} .kpi.t3{border-color:#22c55e;}
    .kpi.t4{border-color:#3b82f6;} .kpi.t5{border-color:#8b5cf6;} .kpi.t6{border-color:#ef4444;}
    .kpi-lbl { font-size:11px;color:var(--grey);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px; }
    .kpi-val { font-size:19px;font-weight:700;color:var(--dark);line-height:1;font-variant-numeric:tabular-nums; }

    .tbl-card { background:var(--white);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden;margin-bottom:24px; }
    .tbl-head { padding:14px 22px;border-bottom:1px solid var(--line);background:#fafbfc;display:flex;align-items:center;justify-content:space-between; }
    .tbl-head h6 { margin:0;font-weight:600;font-size:14px;color:var(--dark); }
    table.main { width:100%;border-collapse:collapse;font-size:13.5px; }
    table.main thead tr { background:var(--dark);color:#fff; }
    table.main thead th { padding:12px 16px;font-weight:600;font-size:11.5px;letter-spacing:.5px;text-transform:uppercase;white-space:nowrap;border:none; }
    table.main tbody tr { border-bottom:1px solid var(--line); }
    table.main tbody td { padding:11px 16px;color:var(--dark);vertical-align:middle; }
    table.main tfoot tr { background:var(--gold-light); }
    table.main tfoot td { padding:13px 16px;color:var(--dark);font-weight:700;font-size:13px;border-top:2px solid var(--gold); }
    .tr { text-align:right; }
    .tc { text-align:center; }
    .hi { font-weight:700;color:var(--teal); }
    .assurance-cell { font-weight:700; color:var(--dark); background:#fafbfc; }
    .foot { text-align:center;color:var(--grey);font-size:12px;margin-top:8px; }

    @media print {
        .filter-card,.btns,.btn-ghost{display:none!important;}
        .wrap{background:#fff;padding:0;}
        .hdr{background:#0d7377!important;-webkit-print-color-adjust:exact;}
    }
</style>

@php
    $dateDebut = \Carbon\Carbon::createFromFormat('Y-m-d', $from)->format('d/m/Y');
    $dateFin   = \Carbon\Carbon::createFromFormat('Y-m-d', $to)->format('d/m/Y');
@endphp

<div class="container">
    <div class="wrap" id="printable">

        <div class="hdr">
            <div class="brand">
                <div class="b-icon"><i class="fa fa-shield-alt"></i></div>
                <div>
                    <p class="b-name">{{ config('app.name', 'APROSAFE') }}</p>
                    <p class="b-sub">Rapport assurance + acte</p>
                </div>
            </div>

            <div class="period">
                Du <strong>{{ $dateDebut }}</strong> au <strong>{{ $dateFin }}</strong>
            </div>

            <div class="btns">
                <a href="#" class="btn-a btn-teal" onclick="exportToExcel()">
                    <i class="fa fa-file-excel"></i> Excel
                </a>
                <a href="#" class="btn-a btn-gold" onclick="window.print()">
                    <i class="fa fa-print"></i> Imprimer
                </a>
                <a href="{{ url()->previous() }}" class="btn-a btn-ghost">
                    <i class="fa fa-arrow-left"></i> Retour
                </a>
            </div>
        </div>

        <form method="GET" action="{{ route('rapports.actes.assurance.detail') }}">
            <div class="filter-card">
                <div>
                    <label>Date début</label>
                    <input type="date" name="from" value="{{ $from }}">
                </div>
                <div>
                    <label>Date fin</label>
                    <input type="date" name="to" value="{{ $to }}">
                </div>
                <button type="submit" class="btn-filter">
                    <i class="fa fa-search me-1"></i> Filtrer
                </button>
            </div>
        </form>

        <div class="kpi-row">
            <div class="kpi t1">
                <div class="kpi-lbl">Assurances</div>
                <div class="kpi-val">{{ number_format($kpi['nb_assurances'],0,',',' ') }}</div>
            </div>
            <div class="kpi t2">
                <div class="kpi-lbl">Types d'actes</div>
                <div class="kpi-val">{{ number_format($kpi['nb_types_actes'],0,',',' ') }}</div>
            </div>
            <div class="kpi t3">
                <div class="kpi-lbl">Lignes</div>
                <div class="kpi-val">{{ number_format($kpi['nb_lignes'],0,',',' ') }}</div>
            </div>
            <div class="kpi t4">
                <div class="kpi-lbl">Nb actes</div>
                <div class="kpi-val">{{ number_format($kpi['nb_actes'],0,',',' ') }}</div>
            </div>
            <div class="kpi t5">
                <div class="kpi-lbl">Montant assurance</div>
                <div class="kpi-val">{{ number_format($kpi['total_assurance'],0,',',' ') }} GNF</div>
            </div>
            <div class="kpi t6">
                <div class="kpi-lbl">Total facturé</div>
                <div class="kpi-val">{{ number_format($kpi['total_general'],0,',',' ') }} GNF</div>
            </div>
        </div>

        <div class="tbl-card">
            <div class="tbl-head">
                <h6>Actes par assurance — détail par acte</h6>
                <small style="color:var(--grey)">
                    {{ count($rapport) }} lignes
                </small>
            </div>

            <div style="overflow-x:auto">
                <table class="main" id="tbl-assurance-acte">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Assurance</th>
                            <th>Acte</th>
                            <th class="tc">Nb actes</th>
                            <th class="tc">Factures</th>
                            <th class="tc">Patients</th>
                            <th class="tr">Montant assurance</th>
                            <th class="tr">Part patient</th>
                            <th class="tr">Total facturé</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rapport as $i => $row)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td class="assurance-cell">{{ $row['assurance'] }}</td>
                                <td>{{ $row['acte'] }}</td>
                                <td class="tc">{{ $row['nb_actes'] }}</td>
                                <td class="tc">{{ $row['nb_factures'] }}</td>
                                <td class="tc">{{ $row['nb_patients'] }}</td>
                                <td class="tr">{{ number_format($row['total_assurance'],0,',',' ') }} GNF</td>
                                <td class="tr">{{ number_format($row['total_patient'],0,',',' ') }} GNF</td>
                                <td class="tr hi">{{ number_format($row['total_general'],0,',',' ') }} GNF</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="tc" style="padding:40px;color:var(--grey)">
                                    Aucune donnée pour cette période
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="tr">Totaux</td>
                            <td class="tc">{{ $totaux['nb_actes'] }}</td>
                            <td class="tc">{{ $totaux['nb_factures'] }}</td>
                            <td class="tc">{{ $totaux['nb_patients'] }}</td>
                            <td class="tr">{{ number_format($totaux['total_assurance'],0,',',' ') }} GNF</td>
                            <td class="tr">{{ number_format($totaux['total_patient'],0,',',' ') }} GNF</td>
                            <td class="tr">{{ number_format($totaux['total_general'],0,',',' ') }} GNF</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="foot">
            Généré le {{ now()->format('d/m/Y à H:i') }} — {{ config('app.name', 'APROSAFE') }}
        </div>
    </div>
</div>

@endsection

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
function exportToExcel() {
    const rows = [
        ['{{ config('app.name', 'APROSAFE') }} — Actes par assurance et par acte'],
        ['Période : du {{ $dateDebut }} au {{ $dateFin }}'],
        [],
        ['#', 'Assurance', 'Acte', 'Nb actes', 'Factures', 'Patients', 'Montant assurance', 'Part patient', 'Total facturé'],
    ];

    document.querySelectorAll('#tbl-assurance-acte tbody tr').forEach((tr) => {
        const cells = [...tr.querySelectorAll('td')].map(td => td.innerText.trim());
        if (cells.length > 1) rows.push(cells);
    });

    rows.push([]);
    rows.push([
        '',
        'TOTAUX',
        '',
        '{{ $totaux["nb_actes"] }}',
        '{{ $totaux["nb_factures"] }}',
        '{{ $totaux["nb_patients"] }}',
        '{{ number_format($totaux["total_assurance"],0,","," ") }} GNF',
        '{{ number_format($totaux["total_patient"],0,","," ") }} GNF',
        '{{ number_format($totaux["total_general"],0,","," ") }} GNF',
    ]);

    const ws = XLSX.utils.aoa_to_sheet(rows);
    ws['!cols'] = [
        {wch:4},
        {wch:28},
        {wch:35},
        {wch:12},
        {wch:12},
        {wch:12},
        {wch:20},
        {wch:18},
        {wch:20}
    ];

    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Assurance par acte');
    XLSX.writeFile(wb, 'actes_par_assurance_detail_{{ $from }}_{{ $to }}.xlsx');
}
</script>