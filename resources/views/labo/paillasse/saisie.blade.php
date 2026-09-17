@extends('layouts.backend')
@section('style') @include('labo.partials.styles') @endsection

@php
    use App\Enums\Labo\StatutExamen;
    use App\Enums\Labo\TypeResultat;
    use App\Support\Labo\ContexteLabo;
    use App\Support\Labo\SelecteurValeurReference;
    $patient = $demande->patient;
    $sexe = ContexteLabo::sexePatient($patient);
    $ageJours = ContexteLabo::ageEnJours($patient, $demande->created_at);
    $modifiable = $ligne->statut->permetSaisie();
    $bacterio = $ligne->examen->estBacteriologie();
@endphp

@section('content')
<div class="container"><div class="page-inner">
    @include('labo.partials.entete', ['titre' => $ligne->examen_nom, 'fil' => [route('labo.paillasse.index') => 'Paillasse', route('labo.demandes.show', $demande) => $demande->numero]])

    <div class="card">
        <div class="card-body d-flex flex-wrap gap-4 align-items-center">
            <div><strong class="fs-5">{{ $patient->full_name }}</strong><br>
                <span class="small">{{ $patient->gender }} · {{ $ageTexte }} @if($demande->grossesse)· <strong>Enceinte</strong>@endif</span></div>
            <div class="small">Demande <strong>{{ $demande->numero }}</strong> @if($demande->urgence)<span class="badge badge-danger">URGENT</span>@endif<br>{{ $demande->nomPrescripteur() }}</div>
            @if($demande->renseignements_cliniques)<div class="small text-muted" style="max-width:360px">{{ $demande->renseignements_cliniques }}</div>@endif
            <span class="badge badge-{{ $ligne->statut->couleur() }} ms-auto fs-6">{{ $ligne->statut->libelle() }}</span>
        </div>
        @if($ageJours === null)
            <div class="alert alert-warning mx-3">Âge du patient inconnu : les normes dépendant de l'âge ne s'appliquent pas. Corrigez la date de naissance dans le dossier patient.</div>
        @endif
        @if($ligne->motif_derniere_rectification && $ligne->statut !== StatutExamen::PUBLIE)
            <div class="alert alert-warning mx-3">Examen rouvert pour rectification : {{ $ligne->motif_derniere_rectification }}</div>
        @endif
    </div>

    @if($critiquesNonSignales->isNotEmpty())
        <div class="card border-danger">
            <div class="card-header bg-danger text-white"><h4 class="card-title text-white mb-0"><i class="fas fa-phone-alt"></i> Valeur(s) critique(s) à signaler au prescripteur</h4></div>
            <div class="card-body">
                @foreach($critiquesNonSignales as $r)
                    <form method="POST" action="{{ route('labo.validation.alerte-critique', $r) }}" class="row g-2 align-items-end border-bottom pb-2 mb-2">@csrf
                        <div class="col-md-3"><strong>{{ $r->libelle }}</strong><br><span class="labo-flag-critique">{{ $r->valeurAffichee() }} {{ $r->unite }} {{ $r->flag->symbole() }}</span></div>
                        <div class="col-md-3"><label class="form-label small">Personne contactée</label><input name="personne_contactee" class="form-control form-control-sm" required value="{{ $demande->nomPrescripteur() }}"></div>
                        <div class="col-md-2"><label class="form-label small">Moyen</label><select name="moyen" class="form-select form-select-sm">@foreach(\App\Models\Labo\LaboAlerteCritique::MOYENS as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select></div>
                        <div class="col-md-3"><label class="form-label small">Commentaire</label><input name="commentaire" class="form-control form-control-sm"></div>
                        <div class="col-md-1"><button class="btn btn-danger btn-sm w-100">Tracé</button></div>
                    </form>
                @endforeach
            </div>
        </div>
    @endif

    <form method="POST" action="{{ $bacterio ? route('labo.paillasse.bacteriologie', $ligne) : route('labo.paillasse.enregistrer', $ligne) }}" class="labo-saisie">@csrf
    <div class="card">
        <div class="card-header d-flex"><h4 class="card-title">Résultats</h4>
            @if($ligne->examen->methode)<span class="ms-auto small text-muted">Méthode : {{ $ligne->examen->methode }}</span>@endif</div>
        <div class="card-body table-responsive">
            @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
            <table class="table table-sm align-middle">
                <thead><tr><th>Paramètre</th><th>Résultat</th><th>Unité</th><th>Norme</th><th>Antériorités</th></tr></thead>
                <tbody>
                @php $groupe = null; @endphp
                @foreach($ligne->examen->parametres->sortBy('ordre') as $p)
                    @if($p->groupe && $p->groupe !== $groupe)
                        @php $groupe = $p->groupe; @endphp
                        <tr><td colspan="5" class="fw-bold bg-light">{{ $groupe }}</td></tr>
                    @endif
                    @php
                        $r = $resultats->get($p->id);
                        $plage = SelecteurValeurReference::choisir($p->valeursReference, $sexe, $ageJours, (bool) $demande->grossesse);
                        $norme = $r?->normeAffichee() ?: ($plage ? ($plage->texte_affiche ?: ($plage->valeur_attendue ?: trim(($plage->min !== null ? $plage->min : '') . ' – ' . ($plage->max !== null ? $plage->max : ''), ' –'))) : '');
                        $ant = $anteriorites->get($p->code, collect());
                    @endphp
                    <tr class="{{ $r?->flag?->estCritique() ? 'labo-ligne-critique' : '' }}">
                        <td>{{ $p->libelle }} @if($p->obligatoire)<span class="text-danger">*</span>@endif</td>
                        <td>
                            @php $valeur = old('valeurs.' . $p->id, $r?->valeurAffichee()); @endphp
                            @if($p->type_resultat === TypeResultat::CALCULE)
                                <span class="{{ $r?->flag?->classeCss() }}">{{ $r?->valeurAffichee() ?: '—' }} {{ $r?->flag?->symbole() }}</span> <span class="small text-muted">(calculé)</span>
                            @elseif($p->type_resultat->utiliseOptions() && $p->options)
                                <select name="valeurs[{{ $p->id }}]" class="form-select form-select-sm" @disabled(! $modifiable)>
                                    <option value=""></option>
                                    @foreach($p->options as $opt)<option @selected($valeur === $opt)>{{ $opt }}</option>@endforeach
                                </select>
                            @elseif($p->type_resultat === TypeResultat::TEXTE)
                                <textarea name="valeurs[{{ $p->id }}]" rows="2" class="form-control form-control-sm" style="min-width:260px" @disabled(! $modifiable)>{{ $valeur }}</textarea>
                            @else
                                <input type="text" name="valeurs[{{ $p->id }}]" value="{{ $valeur }}" class="form-control form-control-sm {{ $r?->flag?->estCritique() ? 'is-invalid' : '' }}" inputmode="decimal" placeholder="{{ $p->valeur_defaut }}" @disabled(! $modifiable)>
                                @if($r?->flag && $r->flag->symbole())<span class="{{ $r->flag->classeCss() }}">{{ $r->flag->symbole() }}</span>@endif
                            @endif
                        </td>
                        <td class="small">{{ $p->unite }}</td>
                        <td class="small">{{ $norme ?: 'non définie' }}</td>
                        <td class="labo-anteriorite">
                            @foreach($ant as $a)
                                <div>{{ \Illuminate\Support\Carbon::parse($a->date_demande)->format('d/m/y') }} : <span class="{{ $a->flag?->classeCss() }}">{{ $a->valeurAffichee() }}</span></div>
                            @endforeach
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <p class="small text-muted mb-0">Saisissez « 1,25 » ou « 1.25 ». Les valeurs « &lt; 0,10 » ou « &gt; 500 » sont acceptées. Toute modification annule la validation technique.</p>
        </div>
    </div>

    @if($bacterio)
        <div class="card">
            <div class="card-header"><h4 class="card-title">Germes isolés et antibiogramme</h4></div>
            <div class="card-body">
                @php $isoles = $ligne->germesIsoles->values(); @endphp
                @for($i = 0; $i < max(2, $isoles->count() + 1); $i++)
                    @php $iso = $isoles->get($i); @endphp
                    <div class="border rounded p-2 mb-3">
                        <div class="row g-2 mb-2">
                            <div class="col-md-6"><label class="form-label small">Germe {{ $i + 1 }}</label>
                                <select name="germes[{{ $i }}][germe_id]" class="form-select form-select-sm" @disabled(! $modifiable)>
                                    <option value="">— Aucun —</option>
                                    @foreach($germes as $g)<option value="{{ $g->id }}" @selected($iso?->germe_id == $g->id)>{{ $g->nom }}</option>@endforeach
                                </select></div>
                            <div class="col-md-6"><label class="form-label small">Numération</label>
                                <input name="germes[{{ $i }}][numeration]" value="{{ $iso?->numeration }}" class="form-control form-control-sm" placeholder="≥ 10^5 UFC/mL" @disabled(! $modifiable)></div>
                        </div>
                        <details @if($iso) open @endif>
                            <summary class="small">Antibiogramme</summary>
                            <div class="row mt-2">
                                @foreach($antibiotiques as $ab)
                                    @php $ag = $iso?->antibiogramme->firstWhere('antibiotique_id', $ab->id); @endphp
                                    <div class="col-md-4 col-sm-6 d-flex align-items-center mb-1">
                                        <span class="small me-2" style="min-width:140px">{{ $ab->nom }}</span>
                                        <select name="germes[{{ $i }}][antibiogramme][{{ $ab->id }}][interpretation]" class="form-select form-select-sm" style="width:70px" @disabled(! $modifiable)>
                                            <option value=""></option>
                                            @foreach($interpretations as $v => $l)<option value="{{ $v }}" @selected($ag?->interpretation === $v)>{{ $v }}</option>@endforeach
                                        </select>
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    </div>
                @endfor
            </div>
        </div>
    @endif

    @if($modifiable)
        <div class="d-flex gap-2 mb-4">
            <button class="btn btn-primary">Enregistrer</button>
            @if(! $bacterio)
                @can('labo.validation.technique')
                    <button name="valider_technique" value="1" class="btn btn-success">Enregistrer et valider techniquement</button>
                @endcan
            @endif
            <a href="{{ route('labo.paillasse.index') }}" class="btn btn-link ms-auto">Retour à la liste</a>
        </div>
    @endif
    </form>

    @if($bacterio && $ligne->statut === StatutExamen::EN_COURS)
        @can('labo.validation.technique')
            <form method="POST" action="{{ route('labo.validation.technique', $ligne) }}" class="mb-4">@csrf<button class="btn btn-success">Valider techniquement</button></form>
        @endcan
    @endif
</div></div>
@endsection
