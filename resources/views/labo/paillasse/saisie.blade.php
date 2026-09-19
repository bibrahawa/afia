@extends('layouts.backend')
@section('style')
    @include('labo.partials.styles')
    <style>
        .ps-table td { vertical-align: top; }
        .ps-table td:first-child { padding-top: 16px; }
        .ps-param { color: var(--hali-encre); font-weight: 600; }
        .ps-valeur { display: flex; align-items: center; gap: 8px; }
        .ps-valeur .form-control, .ps-valeur .form-select { max-width: 200px; min-height: 40px; font-size: .95rem; font-variant-numeric: tabular-nums; }
        .ps-norme { color: var(--hali-discret); font-size: .82rem; white-space: nowrap; }
        .ps-critiques { border-color: #fecaca; }
        .ps-critique { display: grid; grid-template-columns: minmax(140px, 1fr) minmax(160px, 1.2fr) 150px minmax(140px, 1fr) auto; gap: 10px; align-items: end; padding: 12px 18px; border-top: 1px solid #fee2e2; }
        .ps-critique:first-of-type { border-top: 0; }
        .ps-critique label { display: grid; gap: 4px; margin: 0; font-size: .78rem; color: var(--hali-discret); font-weight: 600; }
        .ps-germe { padding: 14px 16px; border: 1px solid var(--hali-bordure); border-radius: 10px; }
        .ps-germe + .ps-germe { margin-top: 12px; }
        .ps-antibio { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 6px 14px; margin-top: 10px; }
        .ps-antibio div { display: flex; align-items: center; justify-content: space-between; gap: 8px; font-size: .84rem; }
        .ps-antibio select { width: 72px; }
        @media (max-width: 991.98px) { .ps-critique { grid-template-columns: 1fr 1fr; } }
    </style>
@endsection

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
<div class="container"><div class="page-inner hl">
    @include('labo.partials.entete', ['titre' => $ligne->examen_nom, 'fil' => [route('labo.paillasse.index') => 'Paillasse', route('labo.demandes.show', $demande) => $demande->numero]])

    {{-- Forme bloc obligatoire : un @php(...) court placé avant un bloc @php … @endphp
         faisait avaler par Blade tout le HTML jusqu'au @endphp suivant (écran cassé). --}}
    @php $initiales = mb_strtoupper(mb_substr((string) $patient->first_name, 0, 1) . mb_substr((string) $patient->last_name, 0, 1)); @endphp
    <section class="hl-bloc {{ $demande->urgence ? 'labo-urgent' : '' }}" style="margin-bottom:16px">
        <div class="lb-patient">
            <span class="hl-avatar {{ $demande->urgence ? 'est-urgent' : '' }}" aria-hidden="true">{{ $initiales }}</span>
            <div>
                <div class="lb-patient-nom">{{ $patient->full_name }}
                    @if($demande->urgence) <span class="labo-pastille-urgent"><i class="fas fa-bolt" aria-hidden="true"></i> Urgent</span>@endif</div>
                <div class="lb-patient-meta">
                    <span>{{ $patient->gender }} · {{ $ageTexte }}</span>
                    @if($demande->grossesse)<span><strong>Enceinte</strong></span>@endif
                    <span>Demande <strong>{{ $demande->numero }}</strong></span>
                    <span>{{ $demande->nomPrescripteur() }}</span>
                </div>
                @if($demande->renseignements_cliniques)<span class="lb-sous" style="margin-top:4px; max-width:620px">{{ $demande->renseignements_cliniques }}</span>@endif
            </div>
            <div class="lb-droite">
                @if($ligne->examen->methode)<span class="lb-sous">Méthode : {{ $ligne->examen->methode }}</span>@endif
                <span class="badge badge-{{ $ligne->statut->couleur() }}" style="font-size:.8rem">{{ $ligne->statut->libelle() }}</span>
            </div>
        </div>
        @if($ageJours === null)
            <p class="lb-alerte lb-alerte-avert" style="margin:0 18px 14px"><i class="fas fa-exclamation-circle mt-1" aria-hidden="true"></i><span>Âge du patient inconnu : les normes qui dépendent de l'âge ne s'appliquent pas. Corrigez la date de naissance dans le dossier patient.</span></p>
        @endif
        @if($ligne->motif_derniere_rectification && $ligne->statut !== StatutExamen::PUBLIE)
            <p class="lb-alerte lb-alerte-avert" style="margin:0 18px 14px"><i class="fas fa-undo mt-1" aria-hidden="true"></i><span>Examen rouvert pour rectification : {{ $ligne->motif_derniere_rectification }}</span></p>
        @endif
    </section>

    @if($critiquesNonSignales->isNotEmpty())
        <section class="hl-bloc ps-critiques" style="margin-bottom:16px">
            <h2 class="hl-bloc-titre" style="color:var(--hali-danger)"><i class="fas fa-phone-alt" aria-hidden="true"></i> Valeur{{ $critiquesNonSignales->count() > 1 ? 's' : '' }} critique{{ $critiquesNonSignales->count() > 1 ? 's' : '' }} à signaler au prescripteur</h2>
            @foreach($critiquesNonSignales as $r)
                <form method="POST" action="{{ route('labo.validation.alerte-critique', $r) }}" class="ps-critique">@csrf
                    <div><span class="lb-fort">{{ $r->libelle }}</span><span class="lb-sous"><span class="labo-flag-critique">{{ $r->valeurAffichee() }} {{ $r->unite }} {{ $r->flag->symbole() }}</span></span></div>
                    <label>Personne contactée<input name="personne_contactee" class="form-control form-control-sm" required value="{{ $demande->nomPrescripteur() }}"></label>
                    <label>Moyen<select name="moyen" class="form-select form-select-sm">@foreach(\App\Models\Labo\LaboAlerteCritique::MOYENS as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select></label>
                    <label>Commentaire<input name="commentaire" class="form-control form-control-sm"></label>
                    <button class="hl-bouton lb-plein-risque">Appel tracé</button>
                </form>
            @endforeach
        </section>
    @endif

    <form method="POST" action="{{ $bacterio ? route('labo.paillasse.bacteriologie', $ligne) : route('labo.paillasse.enregistrer', $ligne) }}" class="labo-saisie">@csrf
    <section class="hl-bloc">
        <h2 class="hl-bloc-titre">Résultats <small>Saisissez « 1,25 » ou « 1.25 » · « &lt; 0,10 » et « &gt; 500 » acceptés</small></h2>
        @if($errors->any())
            <div class="lb-alerte lb-alerte-erreur" style="margin:14px 18px 0" role="alert"><i class="fas fa-exclamation-circle mt-1" aria-hidden="true"></i><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif
        <div class="table-responsive">
            <table class="lb-table ps-table">
                <thead><tr><th>Paramètre</th><th>Résultat</th><th>Unité</th><th>Norme</th><th>Antériorités</th></tr></thead>
                <tbody>
                @php $groupe = null; @endphp
                @foreach($ligne->examen->parametres->sortBy('ordre') as $p)
                    @if($p->groupe && $p->groupe !== $groupe)
                        @php $groupe = $p->groupe; @endphp
                        <tr class="lb-groupe"><td colspan="5">{{ $groupe }}</td></tr>
                    @endif
                    @php
                        $r = $resultats->get($p->id);
                        $plage = SelecteurValeurReference::choisir($p->valeursReference, $sexe, $ageJours, (bool) $demande->grossesse);
                        $norme = $r?->normeAffichee() ?: ($plage ? ($plage->texte_affiche ?: ($plage->valeur_attendue ?: trim(($plage->min !== null ? $plage->min : '') . ' – ' . ($plage->max !== null ? $plage->max : ''), ' –'))) : '');
                        $ant = $anteriorites->get($p->code, collect());
                    @endphp
                    <tr class="{{ $r?->flag?->estCritique() ? 'labo-ligne-critique' : '' }}">
                        <td><span class="ps-param">{{ $p->libelle }}</span> @if($p->obligatoire)<span style="color:var(--hali-danger)" title="Obligatoire">*</span>@endif</td>
                        <td>
                            @php $valeur = old('valeurs.' . $p->id, $r?->valeurAffichee()); @endphp
                            <div class="ps-valeur">
                            @if($p->type_resultat === TypeResultat::CALCULE)
                                <span class="{{ $r?->flag?->classeCss() }}">{{ $r?->valeurAffichee() ?: '—' }} {{ $r?->flag?->symbole() }}</span> <span class="lb-sous" style="display:inline">(calculé)</span>
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
                            </div>
                        </td>
                        <td style="font-size:.84rem">{{ $p->unite }}</td>
                        <td class="ps-norme">{{ $norme ?: 'non définie' }}</td>
                        <td class="labo-anteriorite">
                            @forelse($ant as $a)
                                <div>{{ \Illuminate\Support\Carbon::parse($a->date_demande)->format('d/m/y') }} : <span class="{{ $a->flag?->classeCss() }}">{{ $a->valeurAffichee() }}</span></div>
                            @empty
                                <span style="color:#d1d5db">—</span>
                            @endforelse
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <p class="lb-aide" style="padding:0 18px 14px">Toute modification annule la validation technique.</p>
    </section>

    @if($bacterio)
        <section class="hl-bloc" style="margin-top:16px">
            <h2 class="hl-bloc-titre">Germes isolés et antibiogramme</h2>
            <div class="lb-form">
                @php $isoles = $ligne->germesIsoles->values(); @endphp
                @for($i = 0; $i < max(2, $isoles->count() + 1); $i++)
                    @php $iso = $isoles->get($i); @endphp
                    <div class="ps-germe">
                        <div class="lb-deux">
                            <div><label class="form-label">Germe {{ $i + 1 }}</label>
                                <select name="germes[{{ $i }}][germe_id]" class="form-select form-select-sm" @disabled(! $modifiable)>
                                    <option value="">— Aucun —</option>
                                    @foreach($germes as $g)<option value="{{ $g->id }}" @selected($iso?->germe_id == $g->id)>{{ $g->nom }}</option>@endforeach
                                </select></div>
                            <div><label class="form-label">Numération</label>
                                <input name="germes[{{ $i }}][numeration]" value="{{ $iso?->numeration }}" class="form-control form-control-sm" placeholder="≥ 10^5 UFC/mL" @disabled(! $modifiable)></div>
                        </div>
                        <details class="hl-repli" @if($iso) open @endif style="margin-top:10px">
                            <summary style="font-weight:600; font-size:.85rem; color:var(--hali-primaire)">Antibiogramme</summary>
                            <div class="ps-antibio">
                                @foreach($antibiotiques as $ab)
                                    @php $ag = $iso?->antibiogramme->firstWhere('antibiotique_id', $ab->id); @endphp
                                    <div>
                                        <span>{{ $ab->nom }}</span>
                                        <select name="germes[{{ $i }}][antibiogramme][{{ $ab->id }}][interpretation]" class="form-select form-select-sm" @disabled(! $modifiable) aria-label="{{ $ab->nom }}">
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
        </section>
    @endif

    @if($modifiable)
        <div class="lb-barre-bas">
            <button class="hl-bouton">Enregistrer</button>
            @if(! $bacterio)
                @can('labo.validation.technique')
                    <button name="valider_technique" value="1" class="hl-bouton hl-bouton-plein"><i class="fas fa-check" aria-hidden="true"></i> Enregistrer et valider techniquement</button>
                @endcan
            @endif
            <span class="lb-droite"><a href="{{ route('labo.paillasse.index') }}" class="hl-bouton">Retour à la paillasse</a></span>
        </div>
    @endif
    </form>

    @if($bacterio && $ligne->statut === StatutExamen::EN_COURS)
        @can('labo.validation.technique')
            <form method="POST" action="{{ route('labo.validation.technique', $ligne) }}" style="margin-top:12px">@csrf<button class="hl-bouton hl-bouton-plein"><i class="fas fa-check" aria-hidden="true"></i> Valider techniquement</button></form>
        @endcan
    @endif
</div></div>
@endsection
