@extends('layouts.backend')

@php
    $points = $mesures->filter(fn ($m) => $m['mois'] !== null && $m['poids'] !== null)->values();
    $maxPoids = max(1, $points->max('poids') ?? 1);
    $maxMois = max(1, $points->max('mois') ?? 1);
@endphp

@section('content')
<div class="container"><div class="page-inner">
    <div class="page-header d-flex flex-wrap align-items-center gap-2">
        <h3 class="fw-bold mb-0">Croissance — {{ $patient->full_name }}</h3>
        <span class="text-muted small">{{ $naissance ? 'né(e) le ' . $naissance->format('d/m/Y') : 'date de naissance inconnue' }}</span>
        @can('parcours.dossier')<a href="{{ route('parcours.dossier.show', $patient->id) }}" class="btn btn-sm btn-outline-secondary ms-auto">Dossier</a>@endcan
    </div>

    @unless($normesDisponibles)
        <div class="alert alert-info small">
            Les tables de référence de l'OMS ne sont pas encore importées : les mesures sont affichées, sans z-score ni percentile.
            Import : <code>php artisan aprosafe:importer-normes-oms &lt;fichier&gt; poids_age Homme</code>
        </div>
    @endunless

    @unless($naissance)
        <div class="alert alert-warning small">Sans date de naissance sur la fiche patient, l'âge en mois ne peut pas être calculé.</div>
    @endunless

    <div class="row">
        <div class="col-lg-7"><div class="card">
            <div class="card-header"><h4 class="card-title">Poids par âge</h4></div>
            <div class="card-body">
                @if($points->count() > 1)
                    <svg viewBox="0 0 400 200" style="width:100%; height:220px">
                        <line x1="30" y1="170" x2="390" y2="170" stroke="#999"></line>
                        <line x1="30" y1="10" x2="30" y2="170" stroke="#999"></line>
                        <polyline fill="none" stroke="#087f6b" stroke-width="2"
                            points="@foreach($points as $p){{ 30 + ($p['mois'] / $maxMois) * 355 }},{{ 170 - ($p['poids'] / $maxPoids) * 150 }} @endforeach"></polyline>
                        @foreach($points as $p)
                            <circle cx="{{ 30 + ($p['mois'] / $maxMois) * 355 }}" cy="{{ 170 - ($p['poids'] / $maxPoids) * 150 }}" r="3" fill="#087f6b"></circle>
                        @endforeach
                        <text x="30" y="188" font-size="9" fill="#666">0 mois</text>
                        <text x="350" y="188" font-size="9" fill="#666">{{ $maxMois }} mois</text>
                        <text x="2" y="16" font-size="9" fill="#666">{{ number_format($maxPoids, 1, ',', ' ') }} kg</text>
                    </svg>
                @else
                    <p class="text-muted small mb-0">Au moins deux mesures de poids sont nécessaires pour tracer la courbe.</p>
                @endif
            </div>
        </div></div>

        <div class="col-lg-5"><div class="card">
            <div class="card-header"><h4 class="card-title">Mesures</h4></div>
            <div class="card-body table-responsive">
                <table class="table table-sm small align-middle">
                    <thead><tr><th>Date</th><th>Âge</th><th>Poids</th><th>Taille</th><th>Z (poids)</th></tr></thead>
                    <tbody>
                    @forelse($mesures as $m)
                        @php $lecture = $croissance->interpretation($m['z']['poids']); @endphp
                        <tr>
                            <td>{{ $m['date']->format('d/m/Y') }}</td>
                            <td>{{ $m['mois'] !== null ? $m['mois'] . ' mois' : '—' }}</td>
                            <td>{{ $m['poids'] !== null ? rtrim(rtrim(number_format($m['poids'], 2, ',', ''), '0'), ',') . ' kg' : '—' }}</td>
                            <td>{{ $m['taille'] !== null ? rtrim(rtrim(number_format($m['taille'], 1, ',', ''), '0'), ',') . ' cm' : '—' }}</td>
                            <td>
                                @if($m['z']['poids'] !== null)
                                    <span class="badge badge-{{ $lecture['niveau'] }}" title="{{ $lecture['libelle'] }}">{{ number_format($m['z']['poids'], 2, ',', '') }}</span>
                                @else — @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted text-center">Aucune mesure enregistrée.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div></div>
    </div>
</div></div>
@endsection
