@extends('layouts.backend')

@php
    $gnf = fn ($m) => number_format((float) $m, 0, ',', ' ');
    $patient = $consultation->patient;
    $visite = $consultation->visite;
    $initial = [
        'services' => $consultation->services->map(fn ($a) => ['id' => $a->id, 'nom' => $a->name, 'prix' => (float) $a->amount, 'quantite' => 1])->values(),
        'packages' => $consultation->packages->map(fn ($a) => ['id' => $a->id, 'nom' => $a->name, 'prix' => (float) $a->price, 'quantite' => 1])->values(),
        'examens' => $consultation->tests->map(fn ($a) => ['id' => $a->id, 'nom' => $a->name, 'prix' => (float) $a->amount, 'quantite' => 1])->values(),
        'medicaments' => $consultation->medicaments->map(fn ($a) => [
            'id' => $a->id, 'nom' => $a->nom, 'prix' => (float) $a->amount,
            'quantite' => (int) ($a->pivot->quantity ?: 1),
            'dose' => $a->pivot->dose ?: $a->dosage, 'frequence' => $a->pivot->frequence ?: $a->frequence,
            'duree' => $a->pivot->duree ?: $a->duree, 'instructions' => $a->pivot->instructions ?: $a->instructions,
        ])->values(),
    ];
@endphp

@section('style')
<style>
    /* ------------------------------------------------ Consultation */

    /* Puces : diagnostics fréquents, médicaments, modèles, signes, délais */
    .chip {
        display: inline-flex; align-items: center; gap: 6px; min-height: 30px; margin: 0 6px 6px 0; padding: 0 12px;
        border: 1px solid var(--hali-bordure); border-radius: 999px; background: #fff;
        color: var(--hali-texte); font-size: .82rem; font-weight: 500; cursor: pointer;
        transition: background-color .12s ease, border-color .12s ease, color .12s ease;
    }
    .chip:hover { background: var(--hali-primaire-pale); border-color: var(--hali-primaire); color: var(--hali-primaire-fonce); }
    .chip:focus-visible { outline: 2px solid var(--hali-primaire); outline-offset: 2px; }
    #listeSignes .chip { background: var(--hali-primaire-pale); border-color: var(--hali-primaire-clair); color: var(--hali-primaire-fonce); }

    /* Délai du prochain rendez-vous : le choix actif (classe posée par le script) */
    .js-delai.bg-light { background: var(--hali-primaire) !important; border-color: var(--hali-primaire); color: #fff; }

    .panneau-actes { max-height: 230px; overflow-y: auto; }
    .panneau-actes:empty { display: none; }

    /* Récapitulatif : reste visible sous le bandeau patient */
    .recap { position: sticky; top: calc(var(--hali-haut) + var(--bandeau-h, 0px) + 12px); }

    /* Étapes numérotées : l'écran se lit de haut en bas */
    #formConsultation { counter-reset: etape; }
    .card-title.etape::before {
        counter-increment: etape; content: counter(etape);
        display: inline-flex; align-items: center; justify-content: center;
        width: 22px; height: 22px; margin-right: .55rem; border-radius: 50%;
        background: var(--hali-primaire-clair); color: var(--hali-primaire-fonce); font-size: .75rem; font-weight: 700;
    }

    /* Modèles : une barre fine, pas une carte entière */
    .cs-modeles { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 10px; margin-bottom: 16px; padding: 10px 14px; }
    .cs-modeles-titre { color: var(--hali-encre); font-size: .85rem; font-weight: 650; margin-right: 4px; }
    .cs-modeles .chip { margin: 0; }
    .cs-modeles-aide { color: var(--hali-discret); font-size: .82rem; }

    .cs-libelle { display: block; margin-bottom: 6px; color: var(--hali-encre); font-size: .85rem; font-weight: 650; }
    .cs-precedente { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-top: 14px; padding: 10px 12px; border-radius: 10px; background: #f9fafb; color: var(--hali-discret); font-size: .84rem; }
    .cs-precedente .chip { margin: 0; }

    #listeMedicaments .form-control-sm { min-width: 90px; }
    #listeExamens .list-group-item, #listeServices .list-group-item, #listePackages .list-group-item { padding: .55rem .25rem; }

    /* Montant */
    .montant-recap { padding: 14px 16px; border-radius: 10px; background: var(--hali-primaire-pale); }
    .montant-recap .montant { display: block; margin: 2px 0; color: var(--hali-encre); font-size: 1.7rem; font-weight: 700; line-height: 1.2; font-variant-numeric: tabular-nums; }

    /* Actions secondaires : repliées, rangées par usage, sans couleur */
    .actions-secondaires { margin-top: 14px; padding-top: 12px; border-top: 1px solid #f3f4f6; }
    .actions-secondaires > summary { color: var(--hali-primaire); font-size: .88rem; font-weight: 600; padding: 4px 0; }
    .intitule-groupe { margin: 12px 0 4px; color: var(--hali-discret); font-size: .78rem; font-weight: 650; }
    .liste-actions { display: flex; flex-direction: column; }
    .liste-actions > a, .liste-actions > button {
        display: flex; align-items: center; gap: .65rem; width: 100%; padding: .5rem .6rem;
        border: none; border-radius: 8px; background: none; color: var(--hali-texte);
        font-size: .86rem; text-align: left; text-decoration: none;
    }
    .liste-actions > a:hover, .liste-actions > button:hover { background: var(--hali-primaire-pale); color: var(--hali-primaire-fonce); }
    .liste-actions i { width: 1.1rem; color: #9ca3af; text-align: center; }

    @media (max-width: 991.98px) { .recap { position: static; } }
</style>
@endsection


@section('content')
<div class="container-fluid"><div class="page-inner">
    {{-- Un seul bandeau : identité, motif, allergies, grossesse, constantes. Il reste visible au défilement. --}}
    @include('parcours.partials.resume-visite', ['consultation' => $consultation, 'collant' => true, 'retour' => route('parcours.file.index')])


    <form method="POST" action="{{ route('parcours.consultation.enregistrer', $consultation) }}" id="formConsultation">@csrf
        <input type="hidden" name="action" id="champAction" value="terminer">
        <input type="hidden" name="prochain_rdv_jours" id="champJours" value="0">
        <div id="zoneActes"></div>

        <div class="row">
            <div class="col-lg-8">
                {{-- ----------------------------------------- Modèles --}}
                <div class="hl-bloc cs-modeles">
                    <span class="cs-modeles-titre"><i class="fa fa-bolt text-warning" aria-hidden="true"></i> Modèles</span>
                    @forelse($modeles as $m)
                        <button type="button" class="chip js-modele" data-url="{{ route('parcours.consultation.modele', [$consultation, $m]) }}"
                                title="{{ $m->lignes_count }} acte(s){{ $m->estPartage() ? ', partagé' : '' }}">
                            {{ $m->libelle }}
                        </button>
                    @empty
                        <span class="cs-modeles-aide">Aucun pour l'instant. Une consultation terminée peut devenir un modèle : Autres actions › Enregistrer comme modèle.</span>
                    @endforelse
                </div>

                {{-- Grossesse en cours : affichée dans le bandeau patient (terme, DPA, CPN à programmer). --}}

                {{-- ----------------------------------------- Clinique --}}
                <div class="card">
                    <div class="card-header"><h4 class="card-title etape">Examen et diagnostic</h4></div>
                    <div class="card-body">
                        <label class="cs-libelle" for="saisieSigne">Signes cliniques</label>
                        <div class="input-group input-group-sm mb-2">
                            <input type="text" class="form-control" id="saisieSigne" placeholder="Fièvre, céphalées… (Entrée pour ajouter)">
                            <button type="button" class="btn btn-outline-secondary" id="ajouterSigne">Ajouter</button>
                        </div>
                        <div id="listeSignes" class="mb-3"></div>

                        <label class="cs-libelle" for="champDiagnostic">Diagnostic <span class="text-danger">*</span></label>
                        <textarea name="diagnostic" id="champDiagnostic" class="form-control mb-2" rows="2" placeholder="Diagnostic principal">{{ old('diagnostic', $consultation->diagnostic) }}</textarea>
                        <div class="mb-3">
                            @forelse($diagnosticsFrequents as $d)
                                <button type="button" class="chip js-diagnostic" data-valeur="{{ $d }}">{{ $d }}</button>
                            @empty
                                <span class="small text-muted">Vos diagnostics les plus fréquents s'afficheront ici au fil des consultations.</span>
                            @endforelse
                        </div>

                        <label class="cs-libelle">Observation</label>
                        <textarea name="observation" class="form-control" rows="2" placeholder="Facultatif">{{ old('observation', $consultation->observation) }}</textarea>

                        <details class="mt-3 hl-repli">
                            <summary class="text-primary" style="font-weight:600">Allergies, antécédents et traitement en cours <span class="hl-repli-aide">à compléter si besoin</span></summary>
                            <div class="row g-2 mt-1">
                                <div class="col-md-4"><label class="form-label small text-danger">Allergies</label>
                                    <textarea name="antecedents[allergies]" class="form-control form-control-sm" rows="2">{{ $consultation->patient?->antecedant?->allergies }}</textarea></div>
                                <div class="col-md-4"><label class="form-label small">Antécédents médicaux</label>
                                    <textarea name="antecedents[antecedents_medicaux]" class="form-control form-control-sm" rows="2">{{ $consultation->patient?->antecedant?->antecedents_medicaux }}</textarea></div>
                                <div class="col-md-4"><label class="form-label small">Traitement en cours</label>
                                    <textarea name="antecedents[traitements_cours]" class="form-control form-control-sm" rows="2">{{ $consultation->patient?->antecedant?->traitements_cours }}</textarea></div>
                            </div>
                        </details>

                        @if($precedente)
                            <div class="cs-precedente">
                                <i class="fas fa-history" aria-hidden="true"></i>
                                <span>Dernière consultation le {{ $precedente->created_at->format('d/m/Y') }} : <strong>{{ \Illuminate\Support\Str::limit($precedente->diagnostic, 120) }}</strong></span>
                                <button type="button" class="chip js-diagnostic" data-valeur="{{ $precedente->diagnostic }}">Reprendre ce diagnostic</button>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ----------------------------------------- Ordonnance --}}
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4 class="card-title etape">Ordonnance</h4>
                        @if($derniereOrdonnance['lignes'])
                            <button type="button" class="btn btn-sm btn-outline-primary ms-auto" id="renouveler">
                                Renouveler celle du {{ $derniereOrdonnance['date'] }}
                            </button>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            @foreach($medicamentsFrequents as $m)
                                <button type="button" class="chip js-ajout" data-cat="medicaments" data-ligne='@json($m)'>+ {{ $m['nom'] }}</button>
                            @endforeach
                        </div>
                        <input type="text" class="form-control form-control-sm mb-2 js-recherche" data-cat="medicaments" placeholder="Chercher un médicament…">
                        <div class="panneau-actes mb-2 js-resultats" data-cat="medicaments"></div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead><tr><th>Médicament</th><th style="width:80px">Qté</th><th>Dose</th><th>Fréquence</th><th>Durée</th><th>Instructions</th><th></th></tr></thead>
                                <tbody id="listeMedicaments"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- ----------------------------------------- Examens et actes --}}
                <div class="row">
                    <div class="col-md-6"><div class="card">
                        <div class="card-header"><h4 class="card-title etape">Examens</h4></div>
                        <div class="card-body">
                            <div class="mb-2">
                                @foreach($examensFrequents as $e)
                                    <button type="button" class="chip js-ajout" data-cat="examens" data-ligne='@json($e)'>+ {{ $e['nom'] }}</button>
                                @endforeach
                            </div>
                            <input type="text" class="form-control form-control-sm mb-2 js-recherche" data-cat="examens" placeholder="Chercher un examen…">
                            <div class="panneau-actes mb-2 js-resultats" data-cat="examens"></div>
                            <ul class="list-group list-group-flush small" id="listeExamens"></ul>
                        </div>
                    </div></div>
                    <div class="col-md-6"><div class="card">
                        <div class="card-header"><h4 class="card-title etape">Actes et forfaits</h4></div>
                        <div class="card-body">
                            <input type="text" class="form-control form-control-sm mb-2 js-recherche" data-cat="services" placeholder="Chercher un acte…">
                            <div class="panneau-actes mb-2 js-resultats" data-cat="services"></div>
                            <input type="text" class="form-control form-control-sm mb-2 js-recherche" data-cat="packages" placeholder="Chercher un forfait…">
                            <div class="panneau-actes mb-2 js-resultats" data-cat="packages"></div>
                            <ul class="list-group list-group-flush small" id="listeServices"></ul>
                            <ul class="list-group list-group-flush small" id="listePackages"></ul>
                        </div>
                    </div></div>
                </div>
            </div>

            {{-- ----------------------------------------- Récapitulatif --}}
            <div class="col-lg-4">
                <div class="card recap">
                    <div class="card-header"><h4 class="card-title">Récapitulatif</h4></div>
                    <div class="card-body">
                        {{-- Le montant d'abord : c'est ce que le médecin annonce au patient. --}}
                        <div class="montant-recap">
                            <span class="small text-muted d-block">Actes de la consultation</span>
                            <span class="montant"><strong id="totalActes">0</strong> GNF</span>
                            <span class="small text-muted d-block">part assurance et part patient calculées à l'enregistrement</span>
                        </div>

                        <label class="cs-libelle mt-3">Prochain rendez-vous</label>
                        <div class="mb-2">
                            @foreach([0 => 'Aucun', 7 => '1 semaine', 14 => '2 semaines', 30 => '1 mois', 90 => '3 mois'] as $jours => $libelle)
                                <button type="button" class="chip js-delai {{ $jours === 0 ? 'bg-light' : '' }}" data-jours="{{ $jours }}">{{ $libelle }}</button>
                            @endforeach
                        </div>
                        <select name="prochain_rdv_motif_id" class="form-control form-control-sm mb-3">
                            <option value="">Motif : contrôle</option>
                            @foreach($motifs as $motif)<option value="{{ $motif->id }}">{{ $motif->nom }}</option>@endforeach
                        </select>

                        {{-- Une seule action verte : celle qui clôt la consultation. --}}
                        <button type="submit" class="btn btn-success w-100 mb-2" id="boutonTerminer">
                            <i class="fa fa-check"></i> Terminer la consultation
                        </button>
                        <button type="submit" class="btn btn-secondary w-100" id="boutonBrouillon">Enregistrer sans terminer</button>

                        {{-- Tout le reste est secondaire : replié, sobre, et rangé par usage. --}}
                        <details class="actions-secondaires hl-repli">
                            <summary>Autres actions</summary>

                            <p class="intitule-groupe">Pendant la consultation</p>
                            <div class="liste-actions">
                                @can('parcours.constantes')
                                    @if($consultation->visite)
                                        <button type="button" data-bs-toggle="modal" data-bs-target="#modalConstantes">
                                            <i class="fa fa-heartbeat"></i> Prendre les constantes
                                        </button>
                                    @endif
                                @endcan
                                @can('hospitalisation.create')
                                    <a href="{{ route('hospitalisations.create', ['patient' => $consultation->patient_id]) }}">
                                        <i class="fa fa-hospital"></i> Hospitaliser
                                    </a>
                                @endcan
                                @if(\Illuminate\Support\Facades\Route::has('labo.demandes.create'))
                                    @can('labo.demande.create')
                                        <a href="{{ route('labo.demandes.create', ['consultation' => $consultation->id]) }}">
                                            <i class="fa fa-vials"></i> Demande d'analyses
                                        </a>
                                    @endcan
                                @endif
                                @can('labo.reseau.demander')
                                    <a href="{{ route('labo.reseau.create', ['consultation_id' => $consultation->id, 'patient_id' => $consultation->patient_id]) }}">
                                        <i class="fa fa-share-nodes"></i> Analyses — laboratoire partenaire
                                    </a>
                                @endcan
                                @can('parcours.document')
                                    <button type="button" data-bs-toggle="modal" data-bs-target="#modalDocument">
                                        <i class="fa fa-file-medical"></i> Certificat ou arrêt de travail
                                    </button>
                                @endcan
                            </div>

                            <p class="intitule-groupe">Documents et dossier</p>
                            <div class="liste-actions">
                                <a href="{{ route('consultation.rapport.ordonnance.a5', $consultation) }}" target="_blank">
                                    <i class="fa fa-print"></i> Imprimer l'ordonnance
                                </a>
                                <a href="{{ route('consultation.rapport.examens.a5', $consultation) }}" target="_blank">
                                    <i class="fa fa-print"></i> Imprimer les examens
                                </a>
                                @can('parcours.dossier')
                                    <a href="{{ route('parcours.dossier.show', $consultation->patient_id) }}" target="_blank">
                                        <i class="fa fa-folder-open"></i> Dossier du patient
                                    </a>
                                @endcan
                                <a href="{{ route('consultation.show', $consultation) }}">
                                    <i class="fa fa-file-lines"></i> Fiche complète
                                </a>
                                <button type="button" data-bs-toggle="modal" data-bs-target="#modalModele">
                                    <i class="fa fa-bookmark"></i> Enregistrer comme modèle
                                </button>
                            </div>
                        </details>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div></div>

@can('parcours.document')
<div class="modal fade" id="modalDocument" tabindex="-1"><div class="modal-dialog modal-lg">
    <form method="POST" action="{{ route('parcours.documents.store', $consultation) }}" class="modal-content">@csrf
        <div class="modal-header"><h5 class="modal-title">Certificat ou arrêt de travail</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="row g-2 mb-2">
                <div class="col-md-4"><label class="form-label small">Type *</label>
                    <select name="type" id="documentType" class="form-control form-control-sm">
                        @foreach(\App\Enums\Parcours\TypeDocumentMedical::cases() as $typeDocument)
                            <option value="{{ $typeDocument->value }}">{{ $typeDocument->libelle() }}</option>
                        @endforeach
                    </select></div>
                <div class="col-md-3"><label class="form-label small">Début</label><input type="date" name="date_debut" id="documentDebut" class="form-control form-control-sm" value="{{ today()->toDateString() }}"></div>
                <div class="col-md-2"><label class="form-label small">Jours</label><input type="number" min="1" max="365" name="jours" id="documentJours" class="form-control form-control-sm" value="3"></div>
                <div class="col-md-3"><label class="form-label small">Motif</label><input id="documentMotif" class="form-control form-control-sm" maxlength="255" placeholder="Ex. paludisme simple"></div>
            </div>
            <label class="form-label small">Texte du document (modifiable)</label>
            <textarea name="contenu" id="documentContenu" class="form-control" rows="9" required></textarea>
            <p class="small text-muted mb-0 mt-1">Le document est numéroté, conservé au dossier du patient, et s'ouvre en impression après enregistrement.</p>
        </div>
        <div class="modal-footer"><button class="btn btn-primary">Enregistrer et imprimer</button></div>
    </form>
</div></div>
@endcan

@can('parcours.constantes')
    @if($consultation->visite)
        <div class="modal fade" id="modalConstantes" tabindex="-1"><div class="modal-dialog">
            <form method="POST" action="{{ route('parcours.consultation.constantes', $consultation) }}" class="modal-content">@csrf
                <div class="modal-header"><h5 class="modal-title">Constantes</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body row g-2">
                    <div class="col-4"><label class="form-label small">Température (°C)</label><input type="number" step="0.1" name="temperature" class="form-control"></div>
                    <div class="col-4"><label class="form-label small">TA systolique</label><input type="number" name="tension_systolique" class="form-control"></div>
                    <div class="col-4"><label class="form-label small">TA diastolique</label><input type="number" name="tension_diastolique" class="form-control"></div>
                    <div class="col-4"><label class="form-label small">Pouls</label><input type="number" name="pouls" class="form-control"></div>
                    <div class="col-4"><label class="form-label small">SpO₂ (%)</label><input type="number" name="saturation_o2" class="form-control"></div>
                    <div class="col-4"><label class="form-label small">Poids (kg)</label><input type="number" step="0.01" name="poids_kg" class="form-control"></div>
                    <div class="col-12"><input name="notes" class="form-control form-control-sm" maxlength="255" placeholder="Remarque (facultatif)"></div>
                    <div class="col-12 small text-muted">Enregistrer les constantes sauvegarde d'abord la page : pensez à cliquer « Enregistrer sans terminer » avant, si vous avez déjà saisi l'ordonnance.</div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary">Enregistrer les constantes</button></div>
            </form>
        </div></div>
    @endif
@endcan

<div class="modal fade" id="modalModele" tabindex="-1"><div class="modal-dialog">
    <form method="POST" action="{{ route('parcours.consultation.modeles.store', $consultation) }}" class="modal-content">@csrf
        <div class="modal-header"><h5 class="modal-title">Enregistrer comme modèle</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <p class="small text-muted">Le modèle reprend le diagnostic, les signes, l'ordonnance et les examens <strong>déjà enregistrés</strong> sur cette consultation. Enregistrez-la d'abord si vous venez de la modifier.</p>
            <input name="libelle" class="form-control mb-2" maxlength="255" placeholder="Ex. Paludisme simple adulte" required>
            <label class="small d-block"><input type="hidden" name="partager" value="0"><input type="checkbox" name="partager" value="1"> Partager avec les médecins du département</label>
            <label class="small d-block"><input type="hidden" name="avec_observation" value="0"><input type="checkbox" name="avec_observation" value="1"> Inclure l'observation</label>
        </div>
        <div class="modal-footer"><button class="btn btn-primary">Enregistrer</button></div>
    </form>
</div></div>
@endsection

@section('script')
<script>
(function () {
    // Le récapitulatif colle sous le bandeau patient, quelle que soit sa hauteur.
    const bandeau = document.getElementById('bandeauPatient');
    if (!bandeau) return;
    const mesurer = () => document.documentElement.style.setProperty('--bandeau-h', bandeau.offsetHeight + 'px');
    mesurer();
    if (window.ResizeObserver) new ResizeObserver(mesurer).observe(bandeau);
})();

(function () {
    const catalogue = @json($catalogue);
    const derniere = @json($derniereOrdonnance['lignes']);
    const etat = { actes: @json($initial), signes: @json($consultation->signes_cliniques ?? []) };
    const gnf = n => new Intl.NumberFormat('fr-FR').format(Math.round(n || 0));
    const listes = { medicaments: 'listeMedicaments', examens: 'listeExamens', services: 'listeServices', packages: 'listePackages' };

    function ajouter(cat, ligne) {
        if (etat.actes[cat].some(l => Number(l.id) === Number(ligne.id))) return;
        etat.actes[cat].push(Object.assign({ quantite: 1 }, ligne));
        rendre();
    }
    function retirer(cat, id) { etat.actes[cat] = etat.actes[cat].filter(l => Number(l.id) !== Number(id)); rendre(); }

    function champ(cat, index, cle, valeur, taille) {
        return '<input class="form-control form-control-sm js-champ" data-cat="' + cat + '" data-index="' + index + '" data-cle="' + cle +
            '" value="' + (valeur ? String(valeur).replace(/"/g, '&quot;') : '') + '"' + (taille ? ' style="width:' + taille + '"' : '') + '>';
    }

    function rendre() {
        const corps = document.getElementById('listeMedicaments');
        corps.innerHTML = etat.actes.medicaments.map((l, i) =>
            '<tr><td>' + l.nom + '<div class="text-muted small">' + gnf(l.prix) + ' GNF</div></td>' +
            '<td>' + champ('medicaments', i, 'quantite', l.quantite, '70px') + '</td>' +
            '<td>' + champ('medicaments', i, 'dose', l.dose) + '</td>' +
            '<td>' + champ('medicaments', i, 'frequence', l.frequence) + '</td>' +
            '<td>' + champ('medicaments', i, 'duree', l.duree) + '</td>' +
            '<td>' + champ('medicaments', i, 'instructions', l.instructions) + '</td>' +
            '<td class="text-end"><button type="button" class="btn btn-sm btn-link text-danger js-retirer" data-cat="medicaments" data-id="' + l.id + '">&times;</button></td></tr>'
        ).join('') || '<tr><td colspan="7" class="text-muted">Aucun médicament.</td></tr>';

        ['examens', 'services', 'packages'].forEach(function (cat) {
            document.getElementById(listes[cat]).innerHTML = etat.actes[cat].map(l =>
                '<li class="list-group-item d-flex justify-content-between align-items-center">' + l.nom +
                '<span>' + gnf(l.prix) + ' GNF <button type="button" class="btn btn-sm btn-link text-danger js-retirer" data-cat="' + cat + '" data-id="' + l.id + '">&times;</button></span></li>'
            ).join('');
        });

        document.getElementById('listeSignes').innerHTML = etat.signes.map((s, i) =>
            '<button type="button" class="chip js-retirer-signe" data-index="' + i + '">' + s + ' &times;</button>').join('');

        let total = 0;
        Object.keys(etat.actes).forEach(cat => etat.actes[cat].forEach(l => { total += (l.prix || 0) * (cat === 'medicaments' ? (Number(l.quantite) || 1) : 1); }));
        document.getElementById('totalActes').textContent = gnf(total);
    }

    // Recherche dans le catalogue embarqué ; si le catalogue est trop gros pour être
    // envoyé à la page (connexion lente), on interroge le serveur.
    const urlActes = @json(route('parcours.consultation.actes', $consultation));

    function afficherResultats(cat, liste) {
        const zone = document.querySelector('.js-resultats[data-cat="' + cat + '"]');
        zone.innerHTML = liste.slice(0, 12).map(a =>
            '<button type="button" class="chip js-ajout" data-cat="' + cat + '" data-ligne=\'' + JSON.stringify(a).replace(/'/g, '&#39;') + '\'>+ ' + a.nom + ' — ' + gnf(a.prix) + ' GNF</button>'
        ).join('') || '<span class="small text-muted">Aucun résultat.</span>';
    }

    document.querySelectorAll('.js-recherche').forEach(function (input) {
        let minuterie = null;
        input.addEventListener('input', function () {
            const cat = input.dataset.cat, terme = input.value.trim();
            const zone = document.querySelector('.js-resultats[data-cat="' + cat + '"]');
            clearTimeout(minuterie);
            if (terme.length < 2) { zone.innerHTML = ''; return; }

            if ((catalogue[cat] || []).length) {
                afficherResultats(cat, catalogue[cat].filter(a => a.nom.toLowerCase().includes(terme.toLowerCase())));
                return;
            }

            minuterie = setTimeout(function () {
                fetch(urlActes + '?categorie=' + cat + '&q=' + encodeURIComponent(terme), { headers: { 'Accept': 'application/json' } })
                    .then(r => r.ok ? r.json() : [])
                    .then(liste => afficherResultats(cat, liste));
            }, 300);
        });
    });

    // Brouillon local : une coupure réseau ou un refus d'enregistrement ne doit pas
    // effacer ce que le médecin vient de saisir.
    const cleBrouillon = 'consultation-{{ $consultation->id }}';

    function sauverBrouillon() {
        try {
            localStorage.setItem(cleBrouillon, JSON.stringify({
                actes: etat.actes, signes: etat.signes,
                diagnostic: document.getElementById('champDiagnostic').value,
                observation: document.querySelector('textarea[name="observation"]').value,
                le: Date.now(),
            }));
        } catch (e) { /* stockage indisponible : on continue sans brouillon */ }
    }

    function restaurerBrouillon() {
        try {
            const brut = localStorage.getItem(cleBrouillon);
            if (!brut) return;
            const b = JSON.parse(brut);
            if (Date.now() - (b.le || 0) > 12 * 3600 * 1000) { localStorage.removeItem(cleBrouillon); return; }
            if (b.diagnostic && !document.getElementById('champDiagnostic').value) document.getElementById('champDiagnostic').value = b.diagnostic;
            const observation = document.querySelector('textarea[name="observation"]');
            if (b.observation && !observation.value) observation.value = b.observation;
            (b.signes || []).forEach(s => { if (!etat.signes.includes(s)) etat.signes.push(s); });
            Object.keys(b.actes || {}).forEach(cat => (b.actes[cat] || []).forEach(l => ajouter(cat, l)));
        } catch (e) { /* brouillon illisible : on l'ignore */ }
    }

    document.addEventListener('click', function (e) {
        const ajout = e.target.closest('.js-ajout');
        if (ajout) { ajouter(ajout.dataset.cat, JSON.parse(ajout.dataset.ligne)); return; }

        const retrait = e.target.closest('.js-retirer');
        if (retrait) { retirer(retrait.dataset.cat, retrait.dataset.id); return; }

        const signe = e.target.closest('.js-retirer-signe');
        if (signe) { etat.signes.splice(Number(signe.dataset.index), 1); rendre(); return; }

        const diag = e.target.closest('.js-diagnostic');
        if (diag) { document.getElementById('champDiagnostic').value = diag.dataset.valeur; return; }

        const delai = e.target.closest('.js-delai');
        if (delai) {
            document.getElementById('champJours').value = delai.dataset.jours;
            document.querySelectorAll('.js-delai').forEach(b => b.classList.remove('bg-light'));
            delai.classList.add('bg-light');
            return;
        }

        const modele = e.target.closest('.js-modele');
        if (modele) {
            fetch(modele.dataset.url, { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(function (d) {
                    if (d.diagnostic) document.getElementById('champDiagnostic').value = d.diagnostic;
                    (d.signes_cliniques || []).forEach(s => { if (!etat.signes.includes(s)) etat.signes.push(s); });
                    (d.lignes || []).forEach(function (l) {
                        const cat = { service: 'services', package: 'packages', test: 'examens', medicament: 'medicaments' }[l.type];
                        if (cat) ajouter(cat, l);
                    });
                    rendre();
                });
        }
    });

    document.addEventListener('input', function (e) {
        const champModifie = e.target.closest('.js-champ');
        if (!champModifie) return;
        const l = etat.actes[champModifie.dataset.cat][Number(champModifie.dataset.index)];
        l[champModifie.dataset.cle] = champModifie.value;
        if (champModifie.dataset.cle === 'quantite') rendre();
    });

    function ajouterSigne() {
        const saisie = document.getElementById('saisieSigne');
        const valeur = saisie.value.trim();
        if (valeur && !etat.signes.includes(valeur)) { etat.signes.push(valeur); rendre(); }
        saisie.value = '';
    }
    document.getElementById('ajouterSigne').addEventListener('click', ajouterSigne);
    document.getElementById('saisieSigne').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); ajouterSigne(); }
    });

    const renouvelerBouton = document.getElementById('renouveler');
    if (renouvelerBouton) renouvelerBouton.addEventListener('click', function () { derniere.forEach(l => ajouter('medicaments', l)); });

    document.getElementById('boutonBrouillon').addEventListener('click', function () { document.getElementById('champAction').value = 'brouillon'; });
    document.getElementById('boutonTerminer').addEventListener('click', function () { document.getElementById('champAction').value = 'terminer'; });

    // Les lignes et les signes deviennent des champs cachés au moment de l'envoi.
    // Document médical : le texte type se recharge à chaque changement de type ou de durée.
    const urlModele = @json(route('parcours.documents.modele', $consultation));
    const champsDocument = ['documentType', 'documentDebut', 'documentJours', 'documentMotif'].map(id => document.getElementById(id)).filter(Boolean);

    function chargerModeleDocument() {
        const contenu = document.getElementById('documentContenu');
        if (!contenu) return;
        const parametres = new URLSearchParams({
            type: document.getElementById('documentType').value,
            date_debut: document.getElementById('documentDebut').value || '',
            jours: document.getElementById('documentJours').value || '',
            motif: document.getElementById('documentMotif').value || '',
        });
        fetch(urlModele + '?' + parametres.toString(), { headers: { 'Accept': 'application/json' } })
            .then(r => r.ok ? r.json() : null)
            .then(d => { if (d && d.contenu) contenu.value = d.contenu; });
    }

    champsDocument.forEach(champ => champ.addEventListener('change', chargerModeleDocument));
    const modalDocument = document.getElementById('modalDocument');
    if (modalDocument) modalDocument.addEventListener('shown.bs.modal', chargerModeleDocument);

    setInterval(sauverBrouillon, 15000);
    document.addEventListener('input', sauverBrouillon);
    @if(! session('error'))
        try { localStorage.removeItem(cleBrouillon); } catch (e) {}
    @endif
    restaurerBrouillon();

    document.getElementById('formConsultation').addEventListener('submit', function () {
        const zone = document.getElementById('zoneActes');
        zone.innerHTML = '';
        Object.keys(etat.actes).forEach(function (cat) {
            etat.actes[cat].forEach(function (l, i) {
                ['id', 'quantite', 'dose', 'frequence', 'duree', 'instructions'].forEach(function (cle) {
                    if (l[cle] === undefined || l[cle] === null || l[cle] === '') return;
                    const champCache = document.createElement('input');
                    champCache.type = 'hidden';
                    champCache.name = 'actes[' + cat + '][' + i + '][' + cle + ']';
                    champCache.value = l[cle];
                    zone.appendChild(champCache);
                });
            });
        });
        etat.signes.forEach(function (s, i) {
            const champCache = document.createElement('input');
            champCache.type = 'hidden';
            champCache.name = 'signes[' + i + ']';
            champCache.value = s;
            zone.appendChild(champCache);
        });
    });

    rendre();
})();
</script>
@endsection
