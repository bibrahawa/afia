@extends('layouts.backend')

@php
    $valeur = fn ($v) => is_numeric($v) ? number_format((float) $v, 0, ',', ' ') : $v;
    $identite = \App\Support\Etablissement\IdentiteDocument::courante();
    $du = $rapport['periode']['debut']; $au = $rapport['periode']['fin'];
    $periodeTexte = $du->isSameDay($au) ? 'le ' . $du->translatedFormat('d F Y') : 'du ' . $du->translatedFormat('d F Y') . ' au ' . $au->translatedFormat('d F Y');
    $a = today();
    $raccourcis = ["Aujourd'hui" => [$a, $a], '7 jours' => [$a->copy()->subDays(6), $a], 'Ce mois' => [$a->copy()->startOfMonth(), $a],
                   'Mois dernier' => [$a->copy()->subMonthNoOverflow()->startOfMonth(), $a->copy()->subMonthNoOverflow()->endOfMonth()]];
@endphp

@section('style')
<style>
    .rs-filtres { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 10px; padding: 14px 18px; }
    .rs-filtres label { display: block; margin-bottom: 4px; color: var(--hali-encre); font-size: .8rem; font-weight: 650; }
    .rs-filtres input, .rs-filtres select { min-height: 38px; }
    .rs-filtres select { width: auto; max-width: 280px; margin-left: auto; }
    .rs-table { width: 100%; border-collapse: collapse; font-size: .88rem; }
    .rs-table th { position: sticky; top: 0; padding: 10px 14px; background: #fafbfc; border-bottom: 1px solid var(--hali-bordure); color: var(--hali-discret); font-size: .76rem; font-weight: 600; text-align: left; white-space: nowrap; }
    .rs-table td { padding: 10px 14px; border-top: 1px solid #f3f4f6; }
    .rs-table tbody tr:hover td { background: var(--hali-primaire-pale); }
    .rs-table .n { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .rs-table tfoot th { padding: 12px 14px; border-top: 2px solid var(--hali-encre); background: #fff; color: var(--hali-encre); font-size: .88rem; }
    .rs-pied { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 8px; padding: 12px 18px; border-top: 1px solid var(--hali-bordure); color: var(--hali-discret); font-size: .8rem; }
    .rs-impression { display: none; }

    @media print {
        @page { size: A4; margin: 14mm 12mm; }
        .sidebar, .main-header, .no-print, footer, .footer { display: none !important; }
        .main-panel, .container, .page-inner { margin: 0 !important; padding: 0 !important; width: 100% !important; max-width: none !important; }
        .rs-impression { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 2px solid #111; }
        .rs-impression strong { font-size: 15pt; }
        .hl-entete { margin-bottom: 8px; }
        .hl-bloc, .hl-kpi { border: 1px solid #d1d5db !important; box-shadow: none !important; break-inside: avoid; }
        .rs-table th { position: static; }
        .rs-table tr { break-inside: avoid; }
    }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    <div class="rs-impression" aria-hidden="true">
        <div style="display:flex; align-items:center; gap:10px">
            @if($identite->logoDocumentsWeb())<img src="{{ $identite->logoDocumentsWeb() }}" alt="" style="max-height:44px; max-width:140px; object-fit:contain">@endif
            <div><strong>{{ $identite->nom }}</strong><br><small>{{ $identite->coordonnees() }}</small></div>
        </div>
        <small>Édité le {{ now()->format('d/m/Y à H:i') }}</small>
    </div>

    <header class="hl-entete">
        <div>
            <h1>{{ $rapport['titre'] }}</h1>
            <p>{{ ucfirst($periodeTexte) }}</p>
        </div>
        <div class="hl-entete-actions no-print">
            <a href="{{ route('rapports.index') }}" class="hl-bouton"><i class="fas fa-th-large" aria-hidden="true"></i> Tous les rapports</a>
            <a href="{{ route('rapports.csv', $rapport['cle']) }}?{{ http_build_query($filtres) }}" class="hl-bouton"><i class="fas fa-file-excel" aria-hidden="true"></i> Excel</a>
            <button type="button" onclick="window.print()" class="hl-bouton hl-bouton-plein"><i class="fas fa-print" aria-hidden="true"></i> Imprimer</button>
        </div>
    </header>

    <section class="hl-bloc no-print" style="margin-bottom:16px">
        <form method="GET" class="rs-filtres" id="rsForm">
            <div><label for="rsDebut">Du</label><input type="date" name="debut" id="rsDebut" class="form-control" value="{{ $filtres['debut'] }}"></div>
            <div><label for="rsFin">Au</label><input type="date" name="fin" id="rsFin" class="form-control" value="{{ $filtres['fin'] }}"></div>
            <button type="submit" class="hl-bouton">Actualiser</button>
            <div class="hl-puces" role="group" aria-label="Périodes courantes">
                @foreach($raccourcis as $libelle => [$d, $f])
                    <button type="button" class="hl-puce js-periode {{ $d->toDateString() === $filtres['debut'] && $f->toDateString() === $filtres['fin'] ? 'est-actif' : '' }}" data-debut="{{ $d->toDateString() }}" data-fin="{{ $f->toDateString() }}">{{ $libelle }}</button>
                @endforeach
            </div>
            <select class="form-control" aria-label="Changer de rapport" onchange="if (this.value) window.location = this.value">
                @foreach($catalogue as $cle => $autre)
                    <option value="{{ route('rapports.show', $cle) }}?{{ http_build_query($filtres) }}" @selected($cle === $rapport['cle'])>{{ $autre['titre'] }}</option>
                @endforeach
            </select>
        </form>
    </section>

    @if($rapport['indicateurs'])
        <div class="hl-kpis">
            @foreach($rapport['indicateurs'] as $libelle => $chiffre)
                <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">{{ $libelle }}</span><span class="hl-kpi-valeur">{{ $valeur($chiffre) }}</span></div>
            @endforeach
        </div>
    @endif

    <section class="hl-bloc">
        <div class="table-responsive">
            <table class="rs-table">
                <thead><tr>@foreach($rapport['colonnes'] as $colonne)<th class="{{ $loop->first ? '' : 'n' }}">{{ $colonne }}</th>@endforeach</tr></thead>
                <tbody>
                @forelse($rapport['lignes'] as $ligne)
                    <tr>
                        @foreach($ligne as $index => $cellule)
                            <td class="{{ is_numeric($cellule) && $index > 0 ? 'n' : '' }}" @if($loop->first) style="color:var(--hali-encre); font-weight:600" @endif>{{ $valeur($cellule) }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($rapport['colonnes']) }}"><div class="hl-vide" style="padding:28px">Aucune donnée sur cette période.</div></td></tr>
                @endforelse
                </tbody>
                @if($rapport['totaux'])
                    <tfoot><tr>
                        @foreach($rapport['totaux'] as $index => $cellule)
                            <th class="{{ is_numeric($cellule) && $index > 0 ? 'n' : '' }}">{{ $valeur($cellule) }}</th>
                        @endforeach
                    </tr></tfoot>
                @endif
            </table>
        </div>
        <div class="rs-pied">
            <span>@if($rapport['note']){{ $rapport['note'] }}@endif</span>
            <span>Édité le {{ now()->format('d/m/Y à H:i') }} · {{ auth()->user()?->name }}</span>
        </div>
    </section>
</div></div>
@endsection

@section('script')
<script>
document.querySelectorAll('.js-periode').forEach(function (b) {
    b.addEventListener('click', function () {
        document.getElementById('rsDebut').value = b.dataset.debut;
        document.getElementById('rsFin').value = b.dataset.fin;
        document.getElementById('rsForm').submit();
    });
});
</script>
@endsection
