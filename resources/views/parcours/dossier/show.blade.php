@extends('layouts.backend')

@php
    $couleurs = ['consultation' => 'primary', 'hospitalisation' => 'danger', 'laboratoire' => 'info', 'rendez_vous' => 'secondary', 'grossesse' => 'success'];
    $icones = ['consultation' => 'fa-stethoscope', 'hospitalisation' => 'fa-hospital', 'laboratoire' => 'fa-vials', 'rendez_vous' => 'fa-calendar', 'grossesse' => 'fa-baby'];
@endphp

@section('content')
<div class="container"><div class="page-inner">
    <div class="page-header d-flex flex-wrap align-items-center gap-2">
        <h3 class="fw-bold mb-0">Dossier de {{ $patient->full_name }}</h3>
        <span class="text-muted">{{ $patient->gender }}{{ $patient->age !== null ? ', ' . $patient->age . ' ans' : '' }}</span>
        <span class="ms-auto d-flex gap-2">
            <a href="{{ route('parcours.documents.index', $patient->id) }}" class="btn btn-sm btn-outline-secondary">Documents</a>
            @if($patient->dateNaissance() && $patient->dateNaissance()->diffInMonths(today()) <= \App\Services\Parcours\CroissanceService::AGE_MAX_MOIS)
                <a href="{{ route('parcours.croissance.show', $patient->id) }}" class="btn btn-sm btn-outline-info">Croissance</a>
            @endif
            <a href="{{ route('patient.show', $patient->id) }}" class="btn btn-sm btn-outline-secondary">Fiche patient</a>
        </span>
    </div>

    @if($patient->antecedant?->allergies)
        <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <strong>Allergies :</strong> {{ $patient->antecedant->allergies }}</div>
    @endif

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h4 class="card-title">Grossesse</h4></div>
                <div class="card-body small">
                    @if($grossesse)
                        <p class="mb-1"><strong>{{ $grossesse->termeLisible() }}</strong> — accouchement prévu le {{ $grossesse->dpa->format('d/m/Y') }}</p>
                        @php $prochain = $grossesse->prochainContact(); @endphp
                        @if($prochain)<p class="mb-1">Prochaine CPN : {{ $prochain['semaines'] }} SA, vers le {{ $prochain['date_cible']->format('d/m/Y') }}</p>@endif
                        <a href="{{ route('parcours.grossesses.show', $grossesse) }}" class="btn btn-sm btn-outline-success w-100">Ouvrir le suivi</a>
                    @elseif($patient->gender === 'Femme')
                        @can('parcours.grossesse')
                            <form method="POST" action="{{ route('parcours.grossesses.store', $patient->id) }}" class="row g-2">@csrf
                                <div class="col-12"><label class="form-label">Dernières règles</label>
                                    <input type="date" name="ddr" class="form-control form-control-sm" max="{{ today()->toDateString() }}" required></div>
                                <div class="col-6"><label class="form-label">Gestité</label><input type="number" min="1" max="20" name="gestite" class="form-control form-control-sm"></div>
                                <div class="col-6"><label class="form-label">Parité</label><input type="number" min="0" max="20" name="parite" class="form-control form-control-sm"></div>
                                <div class="col-12"><button class="btn btn-sm btn-success w-100">Ouvrir un suivi de grossesse</button></div>
                            </form>
                        @else
                            <span class="text-muted">Aucun suivi en cours.</span>
                        @endcan
                    @else
                        <span class="text-muted">Sans objet.</span>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h4 class="card-title">Filtrer</h4></div>
                <div class="card-body">
                    <form method="GET" class="small">
                        @foreach(\App\Services\Parcours\DossierPatientService::TYPES as $valeur => $libelle)
                            <label class="d-block"><input type="checkbox" name="types[]" value="{{ $valeur }}" @checked(in_array($valeur, $typesChoisis, true))> {{ $libelle }}</label>
                        @endforeach
                        <div class="row g-2 mt-2">
                            <div class="col-6"><label class="form-label">Du</label><input type="date" name="depuis" class="form-control form-control-sm" value="{{ $filtres['depuis'] ?? '' }}"></div>
                            <div class="col-6"><label class="form-label">Au</label><input type="date" name="jusqu_a" class="form-control form-control-sm" value="{{ $filtres['jusqu_a'] ?? '' }}"></div>
                        </div>
                        <button class="btn btn-sm btn-primary w-100 mt-2">Appliquer</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h4 class="card-title">Historique ({{ $evenements->count() }} sur {{ $total }})</h4></div>
                <ul class="list-group list-group-flush">
                    @forelse($evenements as $e)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="badge badge-{{ $couleurs[$e['type']] ?? 'light' }}"><i class="fas {{ $icones[$e['type']] ?? 'fa-circle' }}"></i> {{ \App\Services\Parcours\DossierPatientService::TYPES[$e['type']] }}</span>
                                    <strong class="ms-1">{{ $e['titre'] }}</strong>
                                    <div class="small text-muted">
                                        @foreach($e['details'] as $cle => $valeur)<span>{{ $cle }} : {{ $valeur }}</span>@if(! $loop->last) · @endif @endforeach
                                    </div>
                                </div>
                                <div class="text-end text-nowrap">
                                    <div class="small">{{ $e['date']->format('d/m/Y') }}</div>
                                    @if($e['lien'])<a href="{{ $e['lien'] }}" class="small">Ouvrir</a>@endif
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item text-muted text-center py-4">Aucun événement sur cette période.</li>
                    @endforelse
                </ul>
                @if($total > $evenements->count())
                    <div class="card-footer text-center">
                        <a href="{{ request()->fullUrlWithQuery(['limite' => $limite + 50]) }}" class="btn btn-sm btn-outline-primary">Voir 50 de plus</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div></div>
@endsection
