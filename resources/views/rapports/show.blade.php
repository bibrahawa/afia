@extends('layouts.backend')

@php $valeur = fn ($v) => is_numeric($v) ? number_format((float) $v, 0, ',', ' ') : $v; @endphp

@section('style')
<style>
    @media print {
        .sidebar, .main-header, .no-print, .page-header a, footer { display: none !important; }
        .main-panel, .container, .page-inner { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        .card { border: none !important; box-shadow: none !important; }
    }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner">
    <div class="page-header d-flex flex-wrap align-items-center gap-2">
        <div>
            <h3 class="fw-bold mb-0">{{ $rapport['titre'] }}</h3>
            <div class="small text-muted">Période du {{ $rapport['periode']['debut']->format('d/m/Y') }} au {{ $rapport['periode']['fin']->format('d/m/Y') }}</div>
        </div>
        <span class="ms-auto d-flex gap-2 no-print">
            <a href="{{ route('rapports.csv', $rapport['cle']) }}?{{ http_build_query($filtres) }}" class="btn btn-sm btn-outline-success"><i class="fa fa-file-excel"></i> Export CSV</a>
            <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="fa fa-print"></i> Imprimer</button>
            <a href="{{ route('rapports.index') }}" class="btn btn-sm btn-secondary">Tous les rapports</a>
        </span>
    </div>

    <div class="card no-print"><div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label small">Du</label>
                <input type="date" name="debut" class="form-control form-control-sm" value="{{ $filtres['debut'] }}"></div>
            <div class="col-md-3"><label class="form-label small">Au</label>
                <input type="date" name="fin" class="form-control form-control-sm" value="{{ $filtres['fin'] }}"></div>
            <div class="col-md-3"><button class="btn btn-sm btn-primary w-100">Actualiser</button></div>
            <div class="col-md-3">
                <select class="form-control form-control-sm" onchange="if (this.value) window.location = this.value">
                    @foreach($catalogue as $cle => $autre)
                        <option value="{{ route('rapports.show', $cle) }}?{{ http_build_query($filtres) }}" @selected($cle === $rapport['cle'])>{{ $autre['titre'] }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div></div>

    @if($rapport['indicateurs'])
        <div class="row">
            @foreach($rapport['indicateurs'] as $libelle => $chiffre)
                <div class="col-md-3"><div class="card card-stats card-round"><div class="card-body">
                    <p class="card-category mb-1">{{ $libelle }}</p>
                    <h4 class="card-title mb-0">{{ $valeur($chiffre) }}</h4>
                </div></div></div>
            @endforeach
        </div>
    @endif

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-sm align-middle">
                <thead><tr>@foreach($rapport['colonnes'] as $colonne)<th>{{ $colonne }}</th>@endforeach</tr></thead>
                <tbody>
                @forelse($rapport['lignes'] as $ligne)
                    <tr>
                        @foreach($ligne as $index => $cellule)
                            <td class="{{ is_numeric($cellule) && $index > 0 ? 'text-end' : '' }}">{{ $valeur($cellule) }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($rapport['colonnes']) }}" class="text-center text-muted py-4">Aucune donnée sur cette période.</td></tr>
                @endforelse
                </tbody>
                @if($rapport['totaux'])
                    <tfoot><tr class="fw-bold">
                        @foreach($rapport['totaux'] as $index => $cellule)
                            <th class="{{ is_numeric($cellule) && $index > 0 ? 'text-end' : '' }}">{{ $valeur($cellule) }}</th>
                        @endforeach
                    </tr></tfoot>
                @endif
            </table>

            @if($rapport['note'])<p class="small text-muted mb-0">{{ $rapport['note'] }}</p>@endif
            <p class="small text-muted mb-0">Édité le {{ now()->format('d/m/Y à H:i') }} — {{ auth()->user()?->name }}</p>
        </div>
    </div>
</div></div>
@endsection
