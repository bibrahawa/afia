@extends('layouts.backend')

@php
    $enCours = $hospitalisations->where('statut', 'En cours');
    $aFacturer = $hospitalisations->filter(fn ($h) => $h->statut !== 'En cours' && ! $h->transaction);
    $sortiesDuJour = $enCours->filter(fn ($h) => $h->date_sortie_prevue === today()->format('d/m/Y'));
    $initiales = fn ($p) => $p ? (mb_strtoupper(mb_substr((string) $p->first_name, 0, 1) . mb_substr((string) $p->last_name, 0, 1)) ?: 'P') : '?';
    $dateEntree = fn ($h) => rescue(fn () => \Carbon\Carbon::parse($h->date_entree), null, false);
    $sortiePrevue = fn ($h) => rescue(fn () => \Carbon\Carbon::createFromFormat('d/m/Y', $h->date_sortie_prevue)->startOfDay(), null, false);
@endphp

@section('style')
<style>
    .ho-filtres { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; padding: 14px 18px; border-bottom: 1px solid var(--hali-bordure); }
    .ho-filtres .ho-recherche { position: relative; flex: 1 1 240px; }
    .ho-filtres .ho-recherche i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9ca3af; }
    .ho-filtres .ho-recherche input { width: 100%; min-height: 40px; padding-left: 40px; }
    .ho-ligne { display: grid; grid-template-columns: minmax(200px, 1.4fr) minmax(120px, .8fr) minmax(140px, 1fr) minmax(140px, 1fr) 110px auto; align-items: center; gap: 14px; padding: 12px 18px; border-top: 1px solid #f3f4f6; }
    .ho-ligne:hover { background: var(--hali-primaire-pale); }
    .ho-ligne.est-termine { opacity: .75; }
    .ho-patient { display: flex; align-items: center; gap: 10px; min-width: 0; }
    .ho-patient .hl-avatar { width: 36px; height: 36px; flex-basis: 36px; font-size: .78rem; border-radius: 10px; }
    .ho-patient strong { display: block; color: var(--hali-encre); }
    .ho-sous { display: block; color: var(--hali-discret); font-size: .78rem; }
    .ho-chambre { display: inline-flex; align-items: center; gap: 6px; font-weight: 700; color: var(--hali-encre); }
    .ho-chambre i { color: var(--hali-primaire); }
    .ho-retard { color: var(--hali-danger); font-weight: 700; }
    .ho-actions { display: flex; justify-content: flex-end; align-items: center; gap: 6px; }
    .ho-actions form { margin: 0; }
    .ho-plus { position: relative; }
    .ho-plus > summary { list-style: none; display: inline-grid; place-items: center; width: 34px; height: 34px; border: 1px solid var(--hali-bordure); border-radius: 8px; cursor: pointer; color: var(--hali-texte); }
    .ho-plus > summary::-webkit-details-marker { display: none; }
    .ho-menu { position: absolute; right: 0; top: calc(100% + 6px); z-index: 30; min-width: 190px; padding: 6px; border: 1px solid var(--hali-bordure); border-radius: 10px; background: #fff; box-shadow: var(--hali-ombre-forte); }
    .ho-menu a, .ho-menu button { display: flex; gap: 10px; align-items: center; width: 100%; padding: 8px 10px; border: 0; border-radius: 7px; background: none; color: var(--hali-texte); font-size: .86rem; text-align: left; text-decoration: none; }
    .ho-menu a:hover, .ho-menu button:hover { background: var(--hali-primaire-pale); }
    .ho-menu .est-risque { color: var(--hali-danger); }
    .ho-menu i { width: 16px; color: #9ca3af; }

    /* Fenêtres d'ajout / modification */
    .ho-modal .modal-content { border: 0; border-radius: 14px; }
    .ho-modal .modal-header { padding: 18px 22px 8px; border: 0; }
    .ho-modal .modal-title { color: var(--hali-encre); font-weight: 700; }
    .ho-modal .modal-body { display: grid; gap: 14px; padding: 8px 22px 16px; }
    .ho-modal label { display: block; margin-bottom: 5px; color: var(--hali-encre); font-size: .83rem; font-weight: 650; }
    .ho-modal .modal-footer { padding: 12px 22px 18px; border: 0; }
    .ho-deux { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .ho-filtre-patient { margin-bottom: 6px; }
    @media (max-width: 991.98px) {
        .ho-ligne { grid-template-columns: 1fr 1fr; gap: 8px 14px; }
        .ho-patient { grid-column: 1 / -1; }
        .ho-actions { grid-column: 1 / -1; justify-content: flex-start; }
    }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">

    <header class="hl-entete">
        <div>
            <h1>Hospitalisations</h1>
            <p>Séjours en cours, sorties du jour et séjours à facturer.</p>
        </div>
        <div class="hl-entete-actions">
            @can('chambre.view')
                <a href="{{ route('chambres.index') }}" class="hl-bouton"><i class="fas fa-bed" aria-hidden="true"></i> Plan des chambres</a>
            @endcan
            <button type="button" class="hl-bouton hl-bouton-plein" data-bs-toggle="modal" data-bs-target="#addRowModal" @disabled($chambres->isEmpty())
                    title="{{ $chambres->isEmpty() ? 'Aucune chambre libre' : '' }}">
                <i class="fa fa-plus" aria-hidden="true"></i> Admettre un patient
            </button>
        </div>
    </header>

    <div class="hl-kpis">
        <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">Patients hospitalisés</span><span class="hl-kpi-valeur">{{ $enCours->count() }}</span></div>
        <div class="hl-bloc hl-kpi {{ $chambres->isEmpty() ? 'est-alerte' : '' }}"><span class="hl-kpi-libelle">Chambres libres</span><span class="hl-kpi-valeur">{{ $chambres->count() }}</span></div>
        <div class="hl-bloc hl-kpi {{ $sortiesDuJour->isNotEmpty() ? 'est-alerte' : '' }}"><span class="hl-kpi-libelle">Sorties prévues aujourd'hui</span><span class="hl-kpi-valeur">{{ $sortiesDuJour->count() }}</span></div>
        <div class="hl-bloc hl-kpi {{ $aFacturer->isNotEmpty() ? 'est-danger' : '' }}"><span class="hl-kpi-libelle">Séjours à facturer</span><span class="hl-kpi-valeur">{{ $aFacturer->count() }}</span></div>
    </div>

    <section class="hl-bloc">
        <div class="ho-filtres">
            <label class="ho-recherche mb-0">
                <span class="sr-only visually-hidden">Rechercher</span>
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" id="hoRecherche" class="form-control" placeholder="Patient ou chambre…" autocomplete="off">
            </label>
            <div class="hl-puces" role="group" aria-label="Statut">
                <button type="button" class="hl-puce est-actif" data-filtre="en-cours">En cours <b>{{ $enCours->count() }}</b></button>
                <button type="button" class="hl-puce" data-filtre="a-facturer">À facturer <b>{{ $aFacturer->count() }}</b></button>
                <button type="button" class="hl-puce" data-filtre="tous">Tous <b>{{ $hospitalisations->count() }}</b></button>
            </div>
        </div>

        <div id="hoListe">
            @forelse($hospitalisations as $h)
                @php
                    $p = $h->patient;
                    $entree = $dateEntree($h);
                    $sortie = $sortiePrevue($h);
                    $enCoursLigne = $h->statut === 'En cours';
                    $enRetard = $enCoursLigne && $sortie && $sortie->lt(today());
                    $facturable = ! $h->transaction;
                @endphp
                <div class="ho-ligne {{ $enCoursLigne ? '' : 'est-termine' }}"
                     data-etat="{{ $enCoursLigne ? 'en-cours' : ($facturable ? 'a-facturer' : 'termine') }}"
                     data-recherche="{{ mb_strtolower(($p?->full_name ?? '') . ' ' . $h->chambre?->numero . ' ' . $h->chambre?->type) }}">
                    <div class="ho-patient">
                        <span class="hl-avatar" aria-hidden="true">{{ $initiales($p) }}</span>
                        <div style="min-width:0">
                            <strong>{{ $p?->full_name ?? 'Patient supprimé' }}</strong>
                            @if($h->observation)<span class="ho-sous" title="{{ $h->observation }}">{{ \Illuminate\Support\Str::limit($h->observation, 60) }}</span>@endif
                        </div>
                    </div>
                    <div>
                        <span class="ho-chambre"><i class="fas fa-bed" aria-hidden="true"></i> {{ $h->chambre?->numero ?? '—' }}</span>
                        <span class="ho-sous">{{ $h->chambre?->type }}</span>
                    </div>
                    <div>
                        {{ $entree?->format('d/m/Y') ?? $h->date_entree }}
                        @if($entree && $enCoursLigne)<span class="ho-sous">{{ max(1, (int) $entree->diffInDays(now()) + 1) }}ᵉ jour</span>@endif
                    </div>
                    <div class="{{ $enRetard ? 'ho-retard' : '' }}">
                        {{ $h->date_sortie_prevue }}
                        @if($enRetard)<span class="ho-sous" style="color:var(--hali-danger)">dépassée</span>@elseif($enCoursLigne && $sortie?->isToday())<span class="ho-sous" style="color:var(--hali-alerte); font-weight:700">aujourd'hui</span>@endif
                    </div>
                    <div>
                        @if($enCoursLigne)
                            <span class="hl-statut hl-s-info">En cours</span>
                        @elseif($facturable)
                            <span class="hl-statut hl-s-alerte">À facturer</span>
                        @else
                            <span class="hl-statut hl-s-succes">{{ $h->statut }}</span>
                        @endif
                    </div>
                    <div class="ho-actions">
                        @if($facturable)
                            <form action="{{ route('hospitalisation.paiement', $h->id) }}" method="GET"
                                  onsubmit="return confirm('{{ $enCoursLigne ? 'Clôturer le séjour aujourd\'hui, libérer la chambre et créer la facture ?' : 'Créer la facture de ce séjour ?' }}')">
                                <button class="hl-bouton {{ $enCoursLigne ? '' : 'hl-bouton-plein' }}" style="min-height:34px">{{ $enCoursLigne ? 'Clôturer et facturer' : 'Facturer' }}</button>
                            </form>
                        @endif
                        <details class="ho-plus">
                            <summary title="Autres actions" aria-label="Autres actions"><i class="fas fa-ellipsis-h"></i></summary>
                            <div class="ho-menu">
                                <a href="{{ route('hospitalisations.facture', $h->id) }}" target="_blank"><i class="fas fa-file-invoice"></i> {{ $facturable ? 'Facture provisoire' : 'Facture' }}</a>
                                @if($enCoursLigne)
                                    <button type="button" class="edit-button" data-info="{{ json_encode($h->only(['id', 'patient_id', 'chambre_id', 'date_entree', 'nombre_jours', 'observation'])) }}"><i class="fas fa-pen"></i> Modifier le séjour</button>
                                @endif
                                @if($p && Route::has('parcours.dossier.show'))
                                    @can('parcours.dossier')<a href="{{ route('parcours.dossier.show', $p->id) }}"><i class="fas fa-folder-open"></i> Dossier du patient</a>@endcan
                                @endif
                                @if($facturable)
                                    <form action="{{ route('hospitalisations.destroy', $h->id) }}" method="POST" onsubmit="return confirm('Supprimer ce séjour ? Cette action est définitive.')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="est-risque"><i class="fas fa-trash" style="color:inherit"></i> Supprimer</button>
                                    </form>
                                @endif
                            </div>
                        </details>
                    </div>
                </div>
            @empty
                <div class="hl-vide"><i class="fas fa-procedures" aria-hidden="true"></i>Aucune hospitalisation enregistrée.</div>
            @endforelse
            <div class="hl-vide" id="hoAucun" hidden>Aucun séjour dans cette liste.</div>
        </div>
    </section>

    {{-- ============================================ Admettre un patient --}}
    <div class="modal fade ho-modal" id="addRowModal" tabindex="-1" aria-labelledby="titreAdmission" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form class="modal-content" id="addHospitalisationForm" action="{{ route('hospitalisations.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="titreAdmission">Admettre un patient</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div>
                        <label for="addPatient">Patient</label>
                        <input type="search" class="form-control ho-filtre-patient" placeholder="Filtrer par nom…" data-cible="addPatient" autocomplete="off">
                        <select name="patient_id" id="addPatient" class="form-control" required size="5">
                            @foreach($patients as $patient)
                                <option value="{{ $patient->id }}">{{ $patient->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ho-deux">
                        <div>
                            <label for="addChambre">Chambre libre</label>
                            <select name="chambre_id" id="addChambre" class="form-control" required>
                                @foreach($chambres as $chambre)
                                    <option value="{{ $chambre->id }}">{{ $chambre->numero }} · {{ $chambre->type }} · {{ number_format((float) $chambre->prix_par_jour, 0, ',', ' ') }} GNF/j</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="addEntree">Date d'entrée</label>
                            <input type="date" name="date_entree" id="addEntree" class="form-control" value="{{ today()->toDateString() }}" required>
                        </div>
                        <div>
                            <label for="addJours">Durée prévue (jours)</label>
                            <input type="number" name="nombre_jours" id="addJours" class="form-control" min="1" value="1" required>
                        </div>
                    </div>
                    <div>
                        <label for="addObservation">Observation</label>
                        <textarea name="observation" id="addObservation" class="form-control" rows="2" placeholder="Motif d'admission, consignes…"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="hl-bouton" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" id="addRowButton" class="hl-bouton hl-bouton-plein">Admettre
                        <span class="spinner-border spinner-border-sm" role="status" id="addLoader" style="display:none"></span></button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================ Modifier un séjour --}}
    <div class="modal fade ho-modal" id="editRowModal" tabindex="-1" aria-labelledby="titreModification" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form class="modal-content" id="editHospitalisationForm" action="{{ route('hospitalisation.update') }}" method="POST">
                @csrf @method('PUT')
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="titreModification">Modifier le séjour</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div>
                        <label for="patient_id">Patient</label>
                        <select name="patient_id" id="patient_id" class="form-control" required>
                            @foreach($patients as $patient)
                                <option value="{{ $patient->id }}">{{ $patient->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ho-deux">
                        <div>
                            <label for="chambre_id">Chambre</label>
                            <select name="chambre_id" id="chambre_id" class="form-control" required>
                                @foreach($toutesChambres as $chambre)
                                    <option value="{{ $chambre->id }}">{{ $chambre->numero }} · {{ $chambre->type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="date_entree">Date d'entrée</label>
                            <input type="date" name="date_entree" id="date_entree" class="form-control" required>
                        </div>
                        <div>
                            <label for="nombre_jours">Durée prévue (jours)</label>
                            <input type="number" name="nombre_jours" id="nombre_jours" class="form-control" min="1" required>
                        </div>
                    </div>
                    <div>
                        <label for="observation">Observation</label>
                        <textarea name="observation" id="observation" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="hl-bouton" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="hl-bouton hl-bouton-plein" id="editRowButton">Enregistrer
                        <span class="spinner-border spinner-border-sm" role="status" id="editLoader" style="display:none"></span></button>
                </div>
            </form>
        </div>
    </div>
</div></div>
@endsection

@section('script')
<script>
(function () {
    var filtre = 'en-cours';
    var recherche = document.getElementById('hoRecherche');
    function appliquer() {
        var terme = recherche.value.trim().toLowerCase(), visibles = 0;
        document.querySelectorAll('.ho-ligne').forEach(function (l) {
            var ok = (filtre === 'tous' || l.dataset.etat === filtre) && (!terme || l.dataset.recherche.indexOf(terme) !== -1);
            l.hidden = !ok; if (ok) visibles++;
        });
        var aucun = document.getElementById('hoAucun');
        if (aucun) aucun.hidden = visibles > 0 || !document.querySelector('.ho-ligne');
    }
    document.querySelectorAll('[data-filtre]').forEach(function (b) {
        b.addEventListener('click', function () {
            document.querySelectorAll('[data-filtre]').forEach(function (x) { x.classList.remove('est-actif'); });
            b.classList.add('est-actif'); filtre = b.dataset.filtre; appliquer();
        });
    });
    recherche.addEventListener('input', appliquer);
    appliquer();

    // Filtre de la liste des patients (des centaines de noms dans un simple menu déroulant)
    document.querySelectorAll('.ho-filtre-patient').forEach(function (champ) {
        var liste = document.getElementById(champ.dataset.cible);
        champ.addEventListener('input', function () {
            var t = champ.value.trim().toLowerCase(), premier = null;
            [].forEach.call(liste.options, function (o) {
                var ok = !t || o.text.toLowerCase().indexOf(t) !== -1;
                o.hidden = !ok; if (ok && !premier) premier = o;
            });
            if (premier) liste.value = premier.value;
        });
    });

    // Modifier un séjour
    document.querySelectorAll('.edit-button').forEach(function (b) {
        b.addEventListener('click', function () {
            var h = JSON.parse(b.dataset.info);
            document.getElementById('edit_id').value = h.id;
            document.getElementById('patient_id').value = h.patient_id;
            document.getElementById('chambre_id').value = h.chambre_id;
            document.getElementById('date_entree').value = (h.date_entree || '').substring(0, 10);
            document.getElementById('nombre_jours').value = h.nombre_jours;
            document.getElementById('observation').value = h.observation || '';
            b.closest('details').removeAttribute('open');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('editRowModal')).show();
        });
    });

    // Un seul envoi
    [['addHospitalisationForm', 'addRowButton', 'addLoader'], ['editHospitalisationForm', 'editRowButton', 'editLoader']].forEach(function (t) {
        document.getElementById(t[0]).addEventListener('submit', function () {
            document.getElementById(t[1]).disabled = true; document.getElementById(t[2]).style.display = 'inline-block';
        });
    });
    document.addEventListener('click', function (e) {
        document.querySelectorAll('details.ho-plus[open]').forEach(function (m) { if (!m.contains(e.target)) m.removeAttribute('open'); });
    });
})();
</script>
@endsection
