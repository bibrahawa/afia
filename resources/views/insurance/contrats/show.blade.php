@extends('layouts.backend')

@section('content')
<div class="container"><div class="page-inner">
    @include('assurance.partials.entete', ['titre' => $contrat->libelle ?: 'Police ' . $contrat->numero_police, 'fil' => [route('assurance.contrats.index') => 'Contrats', 0 => $contrat->numero_police]])

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h4 class="card-title">Contrat</h4>
                    @can('assurance.referentiel.manage')
                        <button class="btn btn-sm btn-warning ms-auto" data-bs-toggle="modal" data-bs-target="#modalContrat"><i class="fa fa-edit"></i></button>
                    @endcan
                </div>
                <div class="card-body small">
                    <p class="mb-1"><strong>Organisme payeur :</strong> {{ $contrat->organismePayeur?->name }}</p>
                    <p class="mb-1"><strong>Souscripteur :</strong>
                        @if($contrat->entreprise)<a href="{{ route('assurance.entreprises.show', $contrat->entreprise) }}">{{ $contrat->entreprise->nom }}</a>@else individuel / familial @endif</p>
                    <p class="mb-1"><strong>N° de police :</strong> {{ $contrat->numero_police }}</p>
                    <p class="mb-1"><strong>Période :</strong> {{ $contrat->date_debut->format('d/m/Y') }} → {{ $contrat->date_fin?->format('d/m/Y') ?? '…' }}</p>
                    <p class="mb-0"><strong>Statut :</strong> @include('assurance.partials.statut', ['statut' => $contrat->statut, 'fin' => $contrat->date_fin])</p>
                    @if($contrat->notes)<p class="mt-2 text-muted">{{ $contrat->notes }}</p>@endif
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h4 class="card-title">Formules</h4>
                    @can('assurance.referentiel.manage')
                        <button class="btn btn-sm btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#modalFormule-nouvelle"><i class="fa fa-plus"></i></button>
                    @endcan
                </div>
                <ul class="list-group list-group-flush">
                    @forelse($contrat->formules as $f)
                        <li class="list-group-item small {{ $f->actif ? '' : 'text-muted' }}">
                            <div class="d-flex">
                                <strong>{{ $f->libelle }}</strong>
                                @can('assurance.referentiel.manage')
                                    <button class="btn btn-link btn-sm p-0 ms-auto" data-bs-toggle="modal" data-bs-target="#modalFormule-{{ $f->id }}"><i class="fa fa-edit"></i></button>
                                @endcan
                            </div>
                            {{ rtrim(rtrim(number_format((float) $f->taux_prise_en_charge, 2, ',', ' '), '0'), ',') }} %
                            · plafond/bénéficiaire {{ $f->plafond_annuel_beneficiaire ? number_format((float) $f->plafond_annuel_beneficiaire, 0, ',', ' ') . ' GNF' : 'illimité' }}
                            @if($f->plafond_annuel_famille) · famille {{ number_format((float) $f->plafond_annuel_famille, 0, ',', ' ') }} GNF @endif
                            @if($f->delai_carence_jours) · carence {{ $f->delai_carence_jours }} j @endif
                            · enfants ≤ {{ $f->age_max_enfant }} ans ({{ $f->age_max_enfant_etudiant }} étudiants)
                            @foreach($f->garanties as $g)
                                <div class="text-muted">{{ $g->famille_acte->libelle() }} :
                                    {{ $g->exclu ? 'exclu' : ($g->taux !== null ? rtrim(rtrim(number_format((float) $g->taux, 2, ',', ' '), '0'), ',') . ' %' : 'taux général') }}
                                    @if($g->plafond_par_acte !== null) · max {{ number_format((float) $g->plafond_par_acte, 0, ',', ' ') }} GNF/acte @endif
                                    @if($g->accord_prealable) · accord préalable @endif
                                </div>
                            @endforeach
                        </li>
                    @empty
                        <li class="list-group-item small text-warning">Aucune formule : ajoutez-en une avant d'inscrire des adhérents.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h4 class="card-title">Adhérents</h4></div>
                <div class="card-body">
                    @can('assurance.referentiel.manage')
                        @if($contrat->formules->where('actif', true)->isNotEmpty())
                        <form method="POST" action="{{ route('assurance.adhesions.store', $contrat) }}" class="row g-2 align-items-end mb-3 border-bottom pb-3">@csrf
                            <div class="col-md-6">@include('assurance.partials.choix-patient', ['id' => 'adherent', 'libelle' => 'Adhérent (assuré principal)'])</div>
                            <div class="col-md-6"><label class="form-label">Formule <span class="text-danger">*</span></label>
                                <select name="formule_id" class="form-control" required>
                                    @foreach($contrat->formules->where('actif', true) as $f)<option value="{{ $f->id }}">{{ $f->libelle }}</option>@endforeach
                                </select></div>
                            @if($contrat->entreprise)
                                <div class="col-md-6"><label class="form-label">Emploi dans {{ $contrat->entreprise->nom }}</label>
                                    <select name="patient_emploi_id" class="form-control">
                                        <option value="">— Aucun —</option>
                                        @foreach($emploisDisponibles as $emploi)
                                            <option value="{{ $emploi->id }}">{{ $emploi->patient->full_name }}{{ $emploi->matricule ? ' — ' . $emploi->matricule : '' }}</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Doit correspondre à l'adhérent choisi. <a href="{{ route('assurance.entreprises.show', $contrat->entreprise) }}">Rattacher un employé</a></div>
                                </div>
                            @endif
                            <div class="col-md-3"><label class="form-label">N° de carte</label><input name="numero_carte" class="form-control" maxlength="100"></div>
                            <div class="col-md-3"><label class="form-label">Début <span class="text-danger">*</span></label>
                                <input type="date" name="date_debut" class="form-control" required value="{{ max($contrat->date_debut, today())->toDateString() }}"></div>
                            <div class="col-12"><button class="btn btn-primary"><i class="fa fa-plus"></i> Inscrire l'adhérent</button></div>
                        </form>
                        @endif
                    @endcan

                    <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead><tr><th>Adhérent</th><th>Formule</th><th>Carte</th><th class="text-center">Ayants droit</th><th>Statut</th><th></th></tr></thead>
                        <tbody>
                        @forelse($adhesions as $a)
                            <tr>
                                <td><a href="{{ route('assurance.adhesions.show', $a) }}">{{ $a->patient->full_name }}</a>
                                    @if($a->emploi)<div class="small text-muted">Matricule {{ $a->emploi->matricule ?: '—' }}</div>@endif</td>
                                <td class="small">{{ $a->formule->libelle }}</td>
                                <td class="small">{{ $a->numero_carte ?: '—' }}</td>
                                <td class="text-center">{{ $a->ayants_droit_count }}</td>
                                <td>@include('assurance.partials.statut', ['statut' => $a->statut, 'fin' => $a->date_fin])</td>
                                <td class="text-end"><a href="{{ route('assurance.adhesions.show', $a) }}" class="btn btn-sm btn-outline-primary">Ayants droit</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted text-center">Aucun adhérent.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                    </div>
                    {{ $adhesions->links() }}
                </div>
            </div>
        </div>
    </div>
</div></div>

@can('assurance.referentiel.manage')
<div class="modal fade" id="modalContrat" tabindex="-1"><div class="modal-dialog modal-lg">
    <form method="POST" action="{{ route('assurance.contrats.update', $contrat) }}" class="modal-content">@csrf @method('PUT')
        <div class="modal-header"><h5 class="modal-title">Modifier le contrat</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">@include('assurance.contrats._formulaire', ['contrat' => $contrat])</div>
        <div class="modal-footer"><button class="btn btn-primary">Enregistrer</button></div>
    </form>
</div></div>

<div class="modal fade" id="modalFormule-nouvelle" tabindex="-1"><div class="modal-dialog modal-lg">
    <form method="POST" action="{{ route('assurance.formules.store', $contrat) }}" class="modal-content">@csrf
        <div class="modal-header"><h5 class="modal-title">Nouvelle formule</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">@include('assurance.contrats._formule', ['formule' => null])</div>
        <div class="modal-footer"><button class="btn btn-primary">Enregistrer</button></div>
    </form>
</div></div>

@foreach($contrat->formules as $f)
<div class="modal fade" id="modalFormule-{{ $f->id }}" tabindex="-1"><div class="modal-dialog modal-lg">
    <form method="POST" action="{{ route('assurance.formules.update', $f) }}" class="modal-content">@csrf @method('PUT')
        <div class="modal-header"><h5 class="modal-title">Formule « {{ $f->libelle }} »</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">@include('assurance.contrats._formule', ['formule' => $f])</div>
        <div class="modal-footer"><button class="btn btn-primary">Enregistrer</button></div>
    </form>
</div></div>
@endforeach
@endcan
@endsection

@section('script')
    @include('assurance.partials.choix-patient-script')
@endsection
