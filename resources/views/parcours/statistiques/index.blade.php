@extends('layouts.backend')

@php $nb = fn ($v) => $v === null ? '—' : number_format((float) $v, 0, ',', ' '); @endphp

@section('content')
<div class="container"><div class="page-inner">
    <div class="page-header"><h3 class="fw-bold mb-3">Statistiques de consultation</h3></div>

    <div class="card"><div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label small">Du</label><input type="date" name="depuis" class="form-control form-control-sm" value="{{ $filtres['depuis'] }}"></div>
            <div class="col-md-3"><label class="form-label small">Au</label><input type="date" name="jusqu_a" class="form-control form-control-sm" value="{{ $filtres['jusqu_a'] }}"></div>
            <div class="col-md-4"><label class="form-label small">Médecin</label>
                <select name="medecin_id" class="form-control form-control-sm">
                    <option value="">Tous</option>
                    @foreach($medecins as $m)<option value="{{ $m->id }}" @selected((int) $filtres['medecin_id'] === $m->id)>Dr {{ $m->full_name }}</option>@endforeach
                </select></div>
            <div class="col-md-2"><button class="btn btn-sm btn-primary w-100">Afficher</button></div>
        </form>
    </div></div>

    <div class="row">
        @foreach([
            ['Patients reçus', $stats['visites'], $stats['terminees'] . ' consultation(s) terminée(s)'],
            ['Attente moyenne', $stats['attente_moyenne'] !== null ? $stats['attente_moyenne'] . ' min' : '—', 'médiane ' . ($stats['attente_mediane'] !== null ? $stats['attente_mediane'] . ' min' : '—') . ', max ' . ($stats['attente_max'] !== null ? $stats['attente_max'] . ' min' : '—')],
            ['Rendez-vous non honorés', $stats['rdv_absents'], $stats['taux_absence'] !== null ? $stats['taux_absence'] . ' % des rendez-vous passés' : 'aucun rendez-vous passé'],
            ['Actes facturés', $nb($stats['recette_actes']) . ' GNF', $stats['sans_rendez_vous'] . ' venue(s) sans rendez-vous, ' . $stats['urgences'] . ' urgence(s)'],
        ] as [$titre, $valeur, $detail])
            <div class="col-md-3"><div class="card card-stats card-round"><div class="card-body">
                <p class="card-category mb-1">{{ $titre }}</p>
                <h4 class="card-title mb-1">{{ $valeur }}</h4>
                <p class="small text-muted mb-0">{{ $detail }}</p>
            </div></div></div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-lg-4"><div class="card">
            <div class="card-header"><h4 class="card-title">Par médecin</h4></div>
            <ul class="list-group list-group-flush small">
                @forelse($stats['par_medecin'] as $ligne)
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Dr {{ $ligne['medecin']?->full_name ?? '—' }}</span>
                        <span>{{ $ligne['visites'] }} reçus · {{ $ligne['terminees'] }} vus</span>
                    </li>
                @empty
                    <li class="list-group-item text-muted">Aucune donnée.</li>
                @endforelse
            </ul>
        </div></div>

        <div class="col-lg-4"><div class="card">
            <div class="card-header"><h4 class="card-title">Motifs les plus fréquents</h4></div>
            <ul class="list-group list-group-flush small">
                @forelse($stats['motifs'] as $motif => $total)
                    <li class="list-group-item d-flex justify-content-between"><span>{{ $motif }}</span><span>{{ $total }}</span></li>
                @empty
                    <li class="list-group-item text-muted">Aucune donnée.</li>
                @endforelse
            </ul>
        </div></div>

        <div class="col-lg-4"><div class="card">
            <div class="card-header"><h4 class="card-title">Diagnostics les plus posés</h4></div>
            <ul class="list-group list-group-flush small">
                @forelse($stats['diagnostics'] as $diagnostic => $total)
                    <li class="list-group-item d-flex justify-content-between"><span>{{ $diagnostic }}</span><span>{{ $total }}</span></li>
                @empty
                    <li class="list-group-item text-muted">Aucune donnée.</li>
                @endforelse
            </ul>
        </div></div>
    </div>

    <p class="small text-muted">Période du {{ $stats['periode']['debut']->format('d/m/Y') }} au {{ $stats['periode']['fin']->format('d/m/Y') }} ·
        {{ $stats['parties'] }} patient(s) reparti(s) sans consulter · {{ $stats['en_cours'] }} encore en cours.</p>
</div></div>
@endsection
