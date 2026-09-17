@extends('layouts.backend')

@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

    :root {
        --primary: #0f4c5c;
        --primary-dark: #0b3440;
        --primary-soft: #e8f3f6;
        --accent: #d4a017;
        --accent-soft: #fff6d8;
        --success-soft: #eef8ef;
        --border: #cfd8dc;
        --text: #1f2937;
        --muted: #6b7280;
        --bg: #f4f7f9;
        --white: #ffffff;
        --danger-soft: #fff2f2;
        --shadow: 0 10px 30px rgba(15, 76, 92, 0.08);
        --radius: 16px;
    }

    body {
        font-family: 'Inter', sans-serif;
    }

    .page-wrap {
        background: var(--bg);
        padding: 28px 16px 48px;
        min-height: 100vh;
    }

    .page-inner {
        max-width: 1500px;
        margin: 0 auto;
    }

    .toolbar {
        display: flex;
        justify-content: space-between;
        align-items: end;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }

    .filter-card {
        background: var(--white);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        padding: 18px 20px;
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
        align-items: end;
    }

    .field label {
        display: block;
        font-size: 11px;
        font-weight: 700;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: .6px;
        margin-bottom: 6px;
    }

    .field input,
    .field select {
        height: 42px;
        min-width: 180px;
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 0 12px;
        background: #fff;
        color: var(--text);
        font-size: 14px;
        outline: none;
    }

    .field input:focus,
    .field select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(15, 76, 92, 0.08);
    }

    .btn-row {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .btn-ui {
        height: 42px;
        padding: 0 16px;
        border: none;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-primary { background: var(--primary); color: #fff; }
    .btn-accent  { background: var(--accent); color: #fff; }
    .btn-light   { background: #fff; color: var(--text); border: 1px solid var(--border); }

    .report-card {
        background: var(--white);
        border-radius: 22px;
        box-shadow: var(--shadow);
        overflow: hidden;
        border: 1px solid #e7ecef;
    }

    .report-head {
        background:
            linear-gradient(135deg, rgba(15,76,92,.96), rgba(11,52,64,.96));
        color: #fff;
        padding: 26px 30px 22px;
        position: relative;
    }

    .report-head::after {
        content: "";
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at top right, rgba(255,255,255,.12), transparent 30%);
        pointer-events: none;
    }

    .report-head-top {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        flex-wrap: wrap;
        align-items: center;
        position: relative;
        z-index: 1;
    }

    .report-title-block h1 {
        margin: 0;
        font-size: 24px;
        font-weight: 800;
        letter-spacing: .3px;
    }

    .report-title-block p {
        margin: 6px 0 0;
        color: rgba(255,255,255,.8);
        font-size: 13px;
    }

    .badge-company {
        background: rgba(255,255,255,.12);
        border: 1px solid rgba(255,255,255,.18);
        color: #fff;
        padding: 10px 16px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 700;
        white-space: nowrap;
    }

    .report-meta {
        padding: 18px 24px;
        background: #f9fbfc;
        border-bottom: 1px solid #e8eef1;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 12px;
    }

    .meta-box {
        background: #fff;
        border: 1px solid #e8eef1;
        border-radius: 14px;
        padding: 14px 16px;
    }

    .meta-label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .6px;
        color: var(--muted);
        font-weight: 700;
        margin-bottom: 5px;
    }

    .meta-value {
        font-size: 14px;
        font-weight: 700;
        color: var(--text);
    }

    .table-wrap {
        padding: 22px 22px 20px;
        overflow-x: auto;
    }

    table.report-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        min-width: 1200px;
        color: var(--text);
    }

    .report-table thead th {
        background: #eef3f5;
        color: var(--primary-dark);
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .5px;
        font-weight: 800;
        padding: 14px 10px;
        text-align: center;
        border-top: 1px solid #dbe4e8;
        border-bottom: 1px solid #dbe4e8;
        border-right: 1px solid #dbe4e8;
    }

    .report-table thead tr:first-child th:first-child {
        border-top-left-radius: 12px;
    }

    .report-table thead tr:first-child th:last-child {
        border-top-right-radius: 12px;
    }

    .report-table thead th:first-child,
    .report-table tbody td:first-child,
    .report-table tfoot td:first-child {
        border-left: 1px solid #e3eaee;
    }

    .report-table tbody td {
        padding: 13px 10px;
        border-right: 1px solid #e3eaee;
        border-bottom: 1px solid #e3eaee;
        background: #fff;
        font-size: 13px;
        vertical-align: middle;
    }

    .report-table tbody tr:nth-child(even) td {
        background: #fcfdfd;
    }

    .report-table tbody tr:hover td {
        background: var(--primary-soft);
    }

    .report-table .text-center { text-align: center; }
    .report-table .text-right { text-align: center }
    .report-table .text-bold { font-weight: 700; }

    .col-assure {
        font-weight: 700;
        color: var(--primary-dark);
    }

    .money {
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .tfoot-total td {
        background: #f7f9fa;
        font-weight: 800;
        border-top: 2px solid var(--primary);
    }

    .summary-area {
        padding: 0 22px 24px;
    }

    .summary-grid {
        display: grid;
        grid-template-columns: 1.3fr .9fr;
        gap: 18px;
        align-items: stretch;
    }

    .summary-card,
    .net-card {
        border-radius: 18px;
        overflow: hidden;
        border: 1px solid #e6ecef;
        background: #fff;
    }

    .summary-card .head,
    .net-card .head {
        padding: 14px 18px;
        font-weight: 800;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .summary-card .head {
        background: var(--primary-soft);
        color: var(--primary-dark);
    }

    .net-card .head {
        background: var(--accent-soft);
        color: #7a5b00;
    }

    .summary-card .body,
    .net-card .body {
        padding: 18px;
    }

    .summary-list {
        display: grid;
        gap: 12px;
    }

    .summary-item {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        padding-bottom: 10px;
        border-bottom: 1px dashed #d9e2e6;
    }

    .summary-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .summary-item span:first-child {
        color: var(--muted);
        font-weight: 600;
    }

    .summary-item span:last-child {
        color: var(--text);
        font-weight: 800;
        font-variant-numeric: tabular-nums;
    }

    .net-value {
        font-size: 30px;
        line-height: 1;
        font-weight: 800;
        color: #7a5b00;
        margin-bottom: 8px;
        font-variant-numeric: tabular-nums;
    }

    .net-help {
        color: var(--muted);
        font-size: 13px;
    }

    .empty-box {
        margin: 24px;
        border: 1px dashed #cfd8dc;
        border-radius: 16px;
        padding: 36px 20px;
        text-align: center;
        color: var(--muted);
        background: #fbfcfd;
    }

    .page-foot {
        text-align: center;
        color: var(--muted);
        font-size: 12px;
        padding: 0 10px 20px;
    }

    @media (max-width: 980px) {
        .summary-grid {
            grid-template-columns: 1fr;
        }
    }

    @media print {
        body {
            background: #fff !important;
        }

        .page-wrap {
            background: #fff;
            padding: 0;
        }

        .toolbar {
            display: none !important;
        }

        .page-inner {
            max-width: 100%;
        }

        .report-card {
            box-shadow: none;
            border: none;
            border-radius: 0;
        }

        .report-head {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .report-meta,
        .report-table thead th,
        .summary-card .head,
        .net-card .head,
        .net-card .body,
        .summary-card .body {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .table-wrap {
            padding: 10px 10px 0;
        }

        .summary-area {
            padding: 0 10px 10px;
        }
    }
</style>

@php
    $dateDebut = \Carbon\Carbon::createFromFormat('Y-m-d', $from)->format('d/m/Y');
    $dateFin   = \Carbon\Carbon::createFromFormat('Y-m-d', $to)->format('d/m/Y');
@endphp

<div class="container">
    <div class="page-inner">

        <div class="toolbar">
            <form method="GET" action="{{ route('rapports.bordereau.assurance') }}" class="filter-card">
                <div class="field">
                    <label>Assurance</label>
                    <select name="insurance_company_id" required>
                        @foreach(\App\Models\InsuranceCompany::orderBy('name')->get() as $assurance)
                            <option value="{{ $assurance->id }}" {{ (int) request('insurance_company_id') === (int) $assurance->id ? 'selected' : '' }}>
                                {{ $assurance->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label>Date début</label>
                    <input type="date" name="from" value="{{ $from }}" required>
                </div>

                <div class="field">
                    <label>Date fin</label>
                    <input type="date" name="to" value="{{ $to }}">
                </div>

                <button type="submit" class="btn-ui btn-primary">
                    <i class="fa fa-search"></i> Filtrer
                </button>
            </form>

            <div class="btn-row">
                <button onclick="exportToExcel()" class="btn-ui btn-primary">
                    <i class="fa fa-file-excel"></i> Excel
                </button>
                <button onclick="window.print()" class="btn-ui btn-accent">
                    <i class="fa fa-print"></i> Imprimer
                </button>
                <a href="{{ url()->previous() }}" class="btn-ui btn-light">
                    <i class="fa fa-arrow-left"></i> Retour
                </a>
            </div>
        </div>

        <div class="report-card">
            <div class="report-head">
                <div class="report-head-top">
                    <div class="report-title-block">
                        <h1>Bordereau de facturation assurance</h1>
                        <p>État détaillé des prestations couvertes sur la période sélectionnée</p>
                    </div>

                    <div class="badge-company">
                        SOCIÉTÉ : {{ strtoupper($insuranceCompany->name ?? 'ASSURANCE') }}
                    </div>
                </div>
            </div>

            <div class="report-meta">
                <div class="meta-box">
                    <div class="meta-label">Période</div>
                    <div class="meta-value">Du {{ $dateDebut }} au {{ $dateFin }}</div>
                </div>
                <div class="meta-box">
                    <div class="meta-label">Nombre de lignes</div>
                    <div class="meta-value">{{ number_format(count($lignes), 0, ',', ' ') }}</div>
                </div>
                <div class="meta-box">
                    <div class="meta-label">Date d’édition</div>
                    <div class="meta-value">{{ now()->format('d/m/Y à H:i') }}</div>
                </div>
            </div>

            @if(count($lignes))
                <div class="table-wrap">
                    <table class="report-table" id="tbl-bordereau">
                        <thead>
                            <tr>
                                <th rowspan="2" style="width: 9%;">Date</th>
                                <th rowspan="2" style="width: 20%;">Noms assurés principaux</th>
                                <th rowspan="2" style="width: 18%;">Bénéficiaire</th>
                                <th rowspan="2" style="width: 12%;">N° carte</th>
                                <th rowspan="2" style="width: 16%;">Nature prestation</th>
                                <th rowspan="2" style="width: 13%;">Montant prestation</th>
                                <th colspan="2" style="width: 12%;">Répartition</th>
                            </tr>
                            <tr>
                                <th>Part assurés</th>
                                <th>Part assureurs</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lignes as $ligne)
                                <tr>
                                    <td class="text-center">{{ $ligne['date'] }}</td>
                                    <td class="col-assure">{{ $ligne['nom_assure'] }}</td>
                                    <td>{{ $ligne['beneficiaire'] }}</td>
                                    <td class="text-center">{{ $ligne['numero_carte'] }}</td>
                                    <td>{{ $ligne['nature_prestation'] }}</td>
                                    <td class="text-right money">{{ number_format($ligne['montant_prestation'], 0, ',', ' ') }}</td>
                                    <td class="text-right money">{{ number_format($ligne['part_assure'], 0, ',', ' ') }}</td>
                                    <td class="text-right money text-bold">{{ number_format($ligne['part_assureur'], 0, ',', ' ') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="tfoot-total">
                                <td colspan="5" class="text-center">TOTAL GLOBAL</td>
                                <td class="text-right money">{{ number_format($totaux['montant_prestation'], 0, ',', ' ') }}</td>
                                <td class="text-right money">{{ number_format($totaux['part_assure'], 0, ',', ' ') }}</td>
                                <td class="text-right money">{{ number_format($totaux['part_assureur'], 0, ',', ' ') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="summary-area">
                    <div class="summary-grid">
                        <div class="summary-card">
                            <div class="head">Récapitulatif financier</div>
                            <div class="body">
                                <div class="summary-list">
                                    <div class="summary-item">
                                        <span>Montant total des prestations</span>
                                        <span>{{ number_format($totaux['montant_prestation'], 0, ',', ' ') }} GNF</span>
                                    </div>
                                    <div class="summary-item">
                                        <span>Part assurés</span>
                                        <span>{{ number_format($totaux['part_assure'], 0, ',', ' ') }} GNF</span>
                                    </div>
                                    <div class="summary-item">
                                        <span>Part assureurs</span>
                                        <span>{{ number_format($totaux['part_assureur'], 0, ',', ' ') }} GNF</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="net-card">
                            <div class="head">Net à payer</div>
                            <div class="body">
                                <div class="net-value">
                                    {{ number_format($totaux['part_assureur'], 0, ',', ' ') }} GNF
                                </div>
                                <div class="net-help">
                                    Montant total à régler par l’assurance pour la période sélectionnée.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="empty-box">
                    Aucune donnée disponible pour cette assurance sur la période sélectionnée.
                </div>
            @endif

            <div class="page-foot">
                {{ \App\Support\Etablissement\IdentiteDocument::courante()->nom }} — Bordereau assurance
            </div>
        </div>
    </div>
</div>

@endsection

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
function exportToExcel() {
    const rows = [
        ['BORDEREAU DE FACTURATION ASSURANCE'],
        ['SOCIETE : {{ strtoupper($insuranceCompany->name ?? "ASSURANCE") }}'],
        ['Période : du {{ $dateDebut }} au {{ $dateFin }}'],
        [],
        ['DATE', 'NOMS ASSURÉS PRINCIPAUX', 'BENEFICIAIRE', 'N° CARTE', 'NATURE PRESTATION', 'MONTANT PRESTATION', 'Part assurés', 'Part assureurs'],
    ];

    document.querySelectorAll('#tbl-bordereau tbody tr').forEach((tr) => {
        const cells = [...tr.querySelectorAll('td')].map(td => td.innerText.trim());
        if (cells.length > 1) rows.push(cells);
    });

    rows.push([]);
    rows.push([
        '',
        '',
        '',
        '',
        'TOTAL GLOBAL',
        '{{ number_format($totaux["montant_prestation"], 0, ",", " ") }} GNF',
        '{{ number_format($totaux["part_assure"], 0, ",", " ") }} GNF',
        '{{ number_format($totaux["part_assureur"], 0, ",", " ") }} GNF',
    ]);

    rows.push([
        '',
        '',
        '',
        '',
        '',
        '',
        'NET A PAYER',
        '{{ number_format($totaux["part_assureur"], 0, ",", " ") }} GNF',
    ]);

    const ws = XLSX.utils.aoa_to_sheet(rows);
    ws['!cols'] = [
        {wch:12},
        {wch:30},
        {wch:24},
        {wch:16},
        {wch:24},
        {wch:18},
        {wch:16},
        {wch:18},
    ];

    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Bordereau assurance');
    XLSX.writeFile(wb, 'bordereau_assurance_{{ $insuranceCompany->name }}_{{ $from }}_{{ $to }}.xlsx');
}
</script>