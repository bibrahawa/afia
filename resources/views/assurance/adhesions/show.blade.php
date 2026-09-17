@extends('layouts.backend')

@php
    $contrat = $adhesion->formule->contrat;
    $formule = $adhesion->formule;
    $ouverte = $adhesion->statut !== \App\Enums\Assurance\StatutCouverture::Resiliee;
@endphp

@section('content')
<div class="container"><div class="page-inner">
    @include('assurance.partials.entete', [
        'titre' => 'Adhésion de ' . $adhesion->patient->full_name,
        'fil' => [route('assurance.contrats.index') => 'Contrats', route('assurance.contrats.show', $contrat) => $contrat->numero_police, 0 => $adhesion->patient->full_name],
    ])

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h4 class="card-title">Adhérent</h4></div>
                <div class="card-body small">
                    <p class="mb-1"><strong><a href="{{ route('patient.show', $adhesion->patient_id) }}">{{ $adhesion->patient->full_name }}</a></strong></p>
                    <p class="mb-1"><strong>Contrat :</strong> <a href="{{ route('assurance.contrats.show', $contrat) }}">{{ $contrat->intitule() }}</a></p>
                    <p class="mb-1"><strong>Formule :</strong> {{ $formule->libelle }} — {{ rtrim(rtrim(number_format((float) $formule->taux_prise_en_charge, 2, ',', ' '), '0'), ',') }} %</p>
                    @if($adhesion->emploi)
                        <p class="mb-1"><strong>Employeur :</strong> <a href="{{ route('assurance.entreprises.show', $adhesion->emploi->entreprise) }}">{{ $adhesion->emploi->entreprise->nom }}</a>
                            @if($adhesion->emploi->matricule)(matricule {{ $adhesion->emploi->matricule }})@endif</p>
                    @endif
                    <p class="mb-1"><strong>N° de carte :</strong> {{ $adhesion->numero_carte ?: '—' }}</p>
                    <p class="mb-1"><strong>Depuis le :</strong> {{ $adhesion->date_debut->format('d/m/Y') }}</p>
                    <p class="mb-0"><strong>Statut :</strong> @include('assurance.partials.statut', ['statut' => $adhesion->statut, 'fin' => $adhesion->date_fin])</p>
                </div>
                @can('assurance.referentiel.manage')
                    @if($ouverte)
                    <div class="card-footer">
                        <form method="POST" action="{{ route('assurance.adhesions.cloturer', $adhesion) }}" class="d-flex gap-2"
                              onsubmit="return confirm('Clôturer l\'adhésion ? L\'adhérent ET tous ses ayants droit ne seront plus couverts après cette date.');">@csrf
                            <input type="date" name="date_fin" class="form-control form-control-sm" value="{{ today()->toDateString() }}" required>
                            <button class="btn btn-sm btn-outline-danger text-nowrap">Clôturer l'adhésion</button>
                        </form>
                    </div>
                    @endif
                @endcan
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h4 class="card-title">Bénéficiaires</h4></div>
                <div class="card-body">
                    <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead><tr><th>Personne</th><th>Lien</th><th>Couvert du</th><th>Fin des droits</th><th>Statut</th><th></th></tr></thead>
                        <tbody>
                        @foreach($adhesion->beneficiaires as $b)
                            @php $finAge = $b->finDroitsParAge($formule); @endphp
                            <tr>
                                <td><a href="{{ route('patient.show', $b->patient_id) }}">{{ $b->patient->full_name }}</a>
                                    @if($b->lien === \App\Enums\Assurance\LienBeneficiaire::Enfant && ! \App\Models\Assurance\Beneficiaire::dateNaissance($b->patient))
                                        <div class="small text-warning">Date de naissance inconnue : limite d'âge non appliquée</div>
                                    @endif
                                </td>
                                <td>{{ $b->lien->libelle() }}@if($b->etudiant)<span class="badge badge-light">étudiant</span>@endif</td>
                                <td class="small">{{ optional($b->projection?->start_date)->format('d/m/Y') ?? $b->date_debut->format('d/m/Y') }}
                                    @if($formule->delai_carence_jours)<div class="text-muted">après {{ $formule->delai_carence_jours }} j de carence</div>@endif</td>
                                <td class="small">
                                    {{ optional($b->projection?->end_date)->format('d/m/Y') ?? '—' }}
                                    @if($finAge && (! $b->date_fin || $finAge->lt($b->date_fin)))<div class="text-muted">limite d'âge</div>@endif
                                </td>
                                <td>@include('assurance.partials.statut', ['statut' => $b->statut, 'fin' => $b->projection?->end_date])</td>
                                <td class="text-end">
                                    @can('assurance.referentiel.manage')
                                        @if($b->lien !== \App\Enums\Assurance\LienBeneficiaire::Adherent && $b->statut !== \App\Enums\Assurance\StatutCouverture::Resiliee)
                                            <form method="POST" action="{{ route('assurance.beneficiaires.cloturer', $b) }}" class="d-inline-flex gap-1"
                                                  onsubmit="return confirm('Arrêter la couverture de cette personne à cette date ?');">@csrf
                                                <input type="date" name="date_fin" value="{{ today()->toDateString() }}" class="form-control form-control-sm" required>
                                                <button class="btn btn-sm btn-outline-danger" title="Arrêter la couverture"><i class="fa fa-user-minus"></i></button>
                                            </form>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    </div>

                    @can('assurance.referentiel.manage')
                        @if($ouverte)
                            @if($suggestions->isNotEmpty())
                                <div class="alert alert-info small">
                                    <strong>Proches enregistrés dans les liens familiaux de l'adhérent :</strong>
                                    ils ne sont couverts que s'ils sont déclarés au contrat.
                                    <div class="d-flex flex-wrap gap-2 mt-2">
                                        @foreach($suggestions as $s)
                                            <form method="POST" action="{{ route('assurance.beneficiaires.store', $adhesion) }}">@csrf
                                                <input type="hidden" name="patient_id" value="{{ $s['patient']->id }}">
                                                <input type="hidden" name="lien" value="{{ $s['lien']->value }}">
                                                <button class="btn btn-sm btn-outline-primary"><i class="fa fa-plus"></i> {{ $s['patient']->full_name }} ({{ mb_strtolower($s['lien']->libelle()) }})</button>
                                            </form>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <h6 class="mt-3">Ajouter un ayant droit</h6>
                            <form method="POST" action="{{ route('assurance.beneficiaires.store', $adhesion) }}" class="row g-2 align-items-end">@csrf
                                <div class="col-md-5">@include('assurance.partials.choix-patient', ['id' => 'ayant-droit', 'libelle' => 'Personne à couvrir'])</div>
                                <div class="col-md-3"><label class="form-label">Lien</label>
                                    <select name="lien" class="form-control" required>
                                        @foreach(\App\Enums\Assurance\LienBeneficiaire::ajoutables() as $lien)
                                            <option value="{{ $lien->value }}">{{ $lien->libelle() }}</option>
                                        @endforeach
                                    </select></div>
                                <div class="col-md-2"><label class="form-label">Couvert à partir du</label>
                                    <input type="date" name="date_debut" class="form-control" value="{{ max($adhesion->date_debut, today())->toDateString() }}"></div>
                                <div class="col-md-2">
                                    <input type="hidden" name="etudiant" value="0">
                                    <label class="form-check-label small"><input type="checkbox" name="etudiant" value="1" class="form-check-input"> Étudiant</label>
                                    <button class="btn btn-primary w-100 mt-1">Ajouter</button>
                                </div>
                                <div class="col-12 form-text">
                                    Un conjoint peut aussi avoir sa propre assurance : les deux couvertures s'appliquent.
                                    Plusieurs conjoints peuvent être déclarés. Les enfants sont couverts jusqu'à {{ $formule->age_max_enfant }} ans ({{ $formule->age_max_enfant_etudiant }} ans s'ils sont étudiants).
                                </div>
                            </form>
                        @endif
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div></div>
@endsection

@section('script')
    @include('assurance.partials.choix-patient-script')
@endsection
