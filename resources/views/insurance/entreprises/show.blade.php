@extends('layouts.backend')

@section('content')
<div class="container"><div class="page-inner">
    @include('assurance.partials.entete', ['titre' => $entreprise->nom, 'fil' => [route('assurance.entreprises.index') => 'Entreprises', 0 => $entreprise->nom]])

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h4 class="card-title">Fiche entreprise</h4>
                    @can('assurance.referentiel.manage')
                        <button class="btn btn-sm btn-warning ms-auto" data-bs-toggle="modal" data-bs-target="#modalModifier"><i class="fa fa-edit"></i></button>
                    @endcan
                </div>
                <div class="card-body small">
                    @unless($entreprise->actif)<div class="alert alert-secondary py-1">Entreprise désactivée</div>@endunless
                    <p class="mb-1"><strong>NIF :</strong> {{ $entreprise->nif ?: '—' }}</p>
                    <p class="mb-1"><strong>Secteur :</strong> {{ $entreprise->secteur ?: '—' }}</p>
                    <p class="mb-1"><strong>Adresse :</strong> {{ $entreprise->adresse ?: '—' }}</p>
                    <p class="mb-1"><strong>Contact :</strong> {{ $entreprise->contact_nom ?: '—' }} {{ $entreprise->telephone }} {{ $entreprise->email }}</p>
                    <p class="mb-0"><strong>Paiement des soins :</strong>
                        {{ $entreprise->organismePayeur ? 'convention directe (' . $entreprise->organismePayeur->name . ')' : 'via contrat d\'assurance' }}</p>
                    @if($entreprise->notes)<p class="mt-2 text-muted">{{ $entreprise->notes }}</p>@endif
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h4 class="card-title">Contrats souscrits</h4></div>
                <ul class="list-group list-group-flush">
                    @forelse($entreprise->contrats as $contrat)
                        <li class="list-group-item small">
                            <a href="{{ route('assurance.contrats.show', $contrat) }}">{{ $contrat->intitule() }}</a>
                            <div class="text-muted">Police {{ $contrat->numero_police }} — depuis le {{ $contrat->date_debut->format('d/m/Y') }}</div>
                        </li>
                    @empty
                        <li class="list-group-item small text-muted">Aucun contrat. Créez-le depuis <a href="{{ route('assurance.contrats.index') }}">Contrats</a> en choisissant cette entreprise comme souscripteur.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h4 class="card-title">Personnel rattaché</h4></div>
                <div class="card-body">
                    @can('assurance.referentiel.manage')
                        <form method="POST" action="{{ route('assurance.entreprises.emplois.store', $entreprise) }}" class="row g-2 align-items-end mb-3 border-bottom pb-3">@csrf
                            <div class="col-md-5">@include('assurance.partials.choix-patient', ['id' => 'employe', 'libelle' => 'Employé'])</div>
                            <div class="col-md-2"><label class="form-label">Matricule</label><input name="matricule" class="form-control" maxlength="50"></div>
                            <div class="col-md-2"><label class="form-label">Poste</label><input name="poste" class="form-control" maxlength="255"></div>
                            <div class="col-md-2"><label class="form-label">Depuis</label><input type="date" name="date_debut" class="form-control"></div>
                            <div class="col-md-1"><button class="btn btn-primary w-100" title="Rattacher"><i class="fa fa-plus"></i></button></div>
                        </form>
                    @endcan

                    <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead><tr><th>Employé</th><th>Matricule</th><th>Poste</th><th>Période</th><th></th></tr></thead>
                        <tbody>
                        @forelse($entreprise->emplois as $emploi)
                            <tr class="{{ $emploi->estEnCours() ? '' : 'text-muted' }}">
                                <td><a href="{{ route('patient.show', $emploi->patient_id) }}">{{ $emploi->patient->full_name }}</a></td>
                                <td>{{ $emploi->matricule ?: '—' }}</td>
                                <td>{{ $emploi->poste ?: '—' }}</td>
                                <td class="small">{{ $emploi->date_debut?->format('d/m/Y') ?? '…' }} → {{ $emploi->date_fin?->format('d/m/Y') ?? 'en cours' }}</td>
                                <td class="text-end">
                                    @can('assurance.referentiel.manage')
                                        @if($emploi->estEnCours())
                                            <form method="POST" action="{{ route('assurance.emplois.terminer', $emploi) }}" class="d-inline-flex gap-1"
                                                  onsubmit="return confirm('Enregistrer la fin d\'emploi ? Les adhésions liées à cet emploi seront clôturées à cette date.');">@csrf
                                                <input type="date" name="date_fin" value="{{ today()->toDateString() }}" class="form-control form-control-sm" required>
                                                <button class="btn btn-sm btn-outline-danger" title="Fin d'emploi"><i class="fa fa-sign-out-alt"></i></button>
                                            </form>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted text-center">Aucun employé rattaché.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div></div>

@can('assurance.referentiel.manage')
<div class="modal fade" id="modalModifier" tabindex="-1"><div class="modal-dialog modal-lg">
    <form method="POST" action="{{ route('assurance.entreprises.update', $entreprise) }}" class="modal-content">@csrf @method('PUT')
        <div class="modal-header"><h5 class="modal-title">Modifier l'entreprise</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">@include('assurance.entreprises._formulaire', ['entreprise' => $entreprise])</div>
        <div class="modal-footer"><button class="btn btn-primary">Enregistrer</button></div>
    </form>
</div></div>
@endcan
@endsection

@section('script')
    @include('assurance.partials.choix-patient-script')
@endsection
