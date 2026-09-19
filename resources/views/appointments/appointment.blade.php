@extends('layouts.backend')

@php
    $raccourcis = [
        '' => ['Tous', $stats['total'] ?? 0],
        'today' => ["Aujourd'hui", $stats['today'] ?? 0],
        'tomorrow' => ['Demain', $stats['tomorrow'] ?? 0],
        'day_after_tomorrow' => ['Après-demain', $stats['day_after_tomorrow'] ?? 0],
        'this_week' => ['Cette semaine', $stats['this_week'] ?? 0],
        'next_week' => ['Semaine prochaine', $stats['next_week'] ?? 0],
        'this_month' => ['Ce mois', $stats['this_month'] ?? 0],
    ];
    $filtreDate = request('date_filter', '');
    $statuts = ['pending' => 'À confirmer', 'confirmed' => 'Confirmés', 'completed' => 'Honorés', 'cancelled' => 'Annulés', 'no_show' => 'Absents'];
@endphp

@section('style')
<style>
    .mr-filtres { display: grid; gap: 12px; padding: 14px 18px; border-bottom: 1px solid var(--hali-bordure); }
    .mr-ligne-filtres { display: flex; flex-wrap: wrap; gap: 10px; }
    .mr-recherche { position: relative; flex: 1 1 260px; }
    .mr-recherche i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9ca3af; }
    .mr-recherche input { width: 100%; min-height: 40px; padding-left: 40px; }
    .mr-ligne-filtres select { width: auto; min-width: 180px; min-height: 40px; }

    .mr-jour { margin: 0; padding: 14px 18px 6px; color: var(--hali-encre); font-size: .9rem; font-weight: 700; }
    .mr-jour small { margin-left: 6px; color: #9ca3af; font-weight: 500; }
    .mr-ligne { display: grid; grid-template-columns: 64px minmax(200px, 1.3fr) minmax(170px, 1.2fr) 120px auto; align-items: center; gap: 14px; padding: 11px 18px; border-top: 1px solid #f3f4f6; }
    .mr-ligne:hover { background: var(--hali-primaire-pale); }
    .mr-ligne.est-passe { opacity: .7; }
    .mr-heure { color: var(--hali-encre); font-size: 1.05rem; font-weight: 700; font-variant-numeric: tabular-nums; }
    .mr-patient { display: flex; align-items: center; gap: 10px; min-width: 0; }
    .mr-patient .hl-avatar { width: 34px; height: 34px; flex-basis: 34px; font-size: .75rem; border-radius: 9px; }
    .mr-patient strong { display: block; color: var(--hali-encre); }
    .mr-sous { display: block; color: var(--hali-discret); font-size: .8rem; }
    .mr-notes { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .mr-motif { display: inline-flex; align-items: center; gap: 7px; color: var(--hali-texte); font-size: .88rem; }
    .mr-motif i { width: 9px; height: 9px; border-radius: 3px; flex: none; }
    .mr-actions { display: flex; justify-content: flex-end; align-items: center; gap: 6px; }
    .mr-actions form { margin: 0; }
    .mr-petit { min-height: 32px; padding: 0 12px; font-size: .8rem; }
    .mr-icone { display: inline-grid; place-items: center; width: 32px; height: 32px; border: 1px solid var(--hali-bordure); border-radius: 8px; background: #fff; color: var(--hali-texte); cursor: pointer; text-decoration: none; }
    .mr-icone:hover { border-color: var(--hali-primaire); color: var(--hali-primaire-fonce); background: var(--hali-primaire-pale); text-decoration: none; }
    .mr-icone.est-risque:hover { border-color: var(--hali-danger); color: var(--hali-danger); background: var(--hali-danger-pale); }
    .mr-pagination { padding: 14px 18px; border-top: 1px solid var(--hali-bordure); }
    .mr-pagination nav { display: flex; justify-content: center; }
    @media (max-width: 991.98px) {
        .mr-ligne { grid-template-columns: 56px 1fr; gap: 6px 12px; }
        .mr-ligne > :nth-child(n+3) { grid-column: 2; }
        .mr-actions { justify-content: flex-start; }
    }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">

    <header class="hl-entete">
        <div>
            <h1>Mes rendez-vous</h1>
            <p>{{ $stats['today'] ?? 0 }} aujourd'hui · {{ $stats['tomorrow'] ?? 0 }} demain · {{ $stats['this_week'] ?? 0 }} cette semaine</p>
        </div>
        <div class="hl-entete-actions">
            @can('parcours.file')
                <a href="{{ route('parcours.file.index') }}" class="hl-bouton hl-bouton-plein"><i class="fas fa-users" aria-hidden="true"></i> Ma file d'attente</a>
            @endcan
            @if(Route::has('appointments.export-pdf') && $filtreDate !== '')
                <a href="{{ route('appointments.export-pdf', request()->only(['search', 'status', 'date_filter']) + ['employee_id' => auth()->user()?->employee?->id]) }}" class="hl-bouton" target="_blank" title="Rendez-vous de la période choisie">
                    <i class="fas fa-file-pdf" aria-hidden="true"></i> Exporter en PDF
                </a>
            @endif
        </div>
    </header>

    <section class="hl-bloc">
        <form method="GET" action="{{ route('medecin.appointments') }}" class="mr-filtres" id="mrFiltres">
            <input type="hidden" name="date_filter" value="{{ $filtreDate }}">
            <div class="hl-puces" role="group" aria-label="Période">
                @foreach($raccourcis as $valeur => [$libelle, $nombre])
                    <a class="hl-puce {{ $filtreDate === $valeur ? 'est-actif' : '' }}"
                       href="{{ request()->fullUrlWithQuery(['date_filter' => $valeur ?: null, 'page' => null]) }}">{{ $libelle }} <b>{{ $nombre }}</b></a>
                @endforeach
            </div>
            <div class="mr-ligne-filtres">
                <label class="mr-recherche mb-0">
                    <span class="sr-only visually-hidden">Rechercher</span>
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <input type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Patient, téléphone, motif, notes… puis Entrée">
                </label>
                <select name="status" class="form-control" onchange="this.form.submit()" aria-label="Statut">
                    <option value="">Tous les statuts</option>
                    @foreach($statuts as $valeur => $libelle)
                        <option value="{{ $valeur }}" @selected(request('status') === $valeur)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        <div id="appointmentsList">
            @include('appointments.partials.list')
        </div>
    </section>
</div></div>
@endsection
