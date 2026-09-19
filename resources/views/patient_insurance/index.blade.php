@extends('layouts.backend')

@php
    $gnf = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $pct = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, ',', ''), '0'), ',') . ' %';
    $voirDroits = auth()->user()?->can('assurance.referentiel.view') && Route::has('assurance.droits.show');
    $voirAdhesion = auth()->user()?->can('assurance.referentiel.view') && Route::has('assurance.adhesions.show');

    // État réel d'une couverture (le statut seul ne suffit pas : une date de fin passée la termine).
    $etat = function ($l) {
        if ($l->status === 'suspended') return ['suspendue', 'Suspendue', 'hl-s-alerte'];
        if ($l->status === 'expired' || ($l->end_date && $l->end_date->lt(today()))) return ['terminee', 'Terminée', 'hl-s-neutre'];
        if ($l->start_date && $l->start_date->gt(today())) return ['future', 'À venir', 'hl-s-info'];
        if ($l->end_date && today()->diffInDays($l->end_date, false) <= 30) return ['encours', 'Expire bientôt', 'hl-s-alerte'];
        return ['encours', 'En cours', 'hl-s-succes'];
    };
    $lignes = $patientInsurances->map(fn ($l) => ['l' => $l, 'etat' => $etat($l)]);
    $enCours = $lignes->filter(fn ($x) => $x['etat'][0] === 'encours');
    $bientot = $lignes->filter(fn ($x) => $x['etat'][1] === 'Expire bientôt')->count();
@endphp

@section('style')
<style>
    .pa-outils { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; padding: 14px 18px; border-bottom: 1px solid var(--hali-bordure); }
    .pa-outils input[type=search] { flex: 1 1 240px; min-height: 40px; }
    .pa-outils select { width: auto; min-height: 40px; }
    .pa-ligne { display: grid; grid-template-columns: minmax(200px, 1.3fr) minmax(170px, 1fr) 90px minmax(150px, .9fr) minmax(130px, .8fr) 76px; align-items: center; gap: 14px; padding: 13px 18px; border-top: 1px solid #f3f4f6; }
    .pa-ligne:first-of-type { border-top: 0; }
    .pa-ligne.est-terminee { background: #fafafa; }
    .pa-ligne.est-terminee .pa-nom { color: var(--hali-discret); }
    .pa-nom { display: block; color: var(--hali-encre); font-weight: 650; }
    .pa-nom a { color: inherit; }
    .pa-sous { display: block; color: var(--hali-discret); font-size: .78rem; }
    .pa-type { display: inline-block; margin-top: 3px; padding: 1px 8px; border-radius: 999px; background: #f3f4f6; color: var(--hali-texte); font-size: .7rem; font-weight: 650; }
    .pa-taux { color: var(--hali-primaire-fonce); font-size: 1.05rem; font-weight: 800; font-variant-numeric: tabular-nums; }
    .pa-barre { height: 6px; margin-top: 5px; border-radius: 999px; background: #eef2f2; overflow: hidden; }
    .pa-barre span { display: block; height: 100%; border-radius: inherit; background: var(--hali-primaire); }
    .pa-barre.est-bas span { background: #d97706; }
    .pa-barre.est-epuise span { background: var(--hali-danger); }
    .pa-epuise { color: var(--hali-danger); font-weight: 700; }
    .pa-actions { display: flex; justify-content: flex-end; gap: 4px; }
    .pa-actions form { margin: 0; }
    .pa-bouton { display: inline-grid; place-items: center; width: 34px; height: 34px; border: 1px solid var(--hali-bordure); border-radius: 8px; background: #fff; color: var(--hali-texte); cursor: pointer; text-decoration: none; }
    .pa-bouton:hover { border-color: var(--hali-primaire); color: var(--hali-primaire-fonce); background: var(--hali-primaire-pale); text-decoration: none; }
    .pa-bouton.est-risque:hover { border-color: var(--hali-danger); color: var(--hali-danger); background: var(--hali-danger-pale); }
    .pa-entete { display: grid; grid-template-columns: minmax(200px, 1.3fr) minmax(170px, 1fr) 90px minmax(150px, .9fr) minmax(130px, .8fr) 76px; gap: 14px; padding: 10px 18px; background: #fafbfc; border-bottom: 1px solid var(--hali-bordure); color: var(--hali-discret); font-size: .76rem; font-weight: 600; }
    .pa-modal .modal-content { border: 0; border-radius: 16px; }
    .pa-modal .modal-body { display: grid; gap: 14px; padding: 6px 24px 16px; }
    .pa-modal .modal-header { padding: 18px 24px 8px; border: 0; }
    .pa-modal .modal-footer { padding: 8px 24px 20px; border: 0; }
    .pa-modal label { display: block; margin-bottom: 5px; color: var(--hali-encre); font-size: .83rem; font-weight: 650; }
    .pa-deux { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .pa-trois { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
    .pa-patient-fixe { padding: 10px 12px; border-radius: 10px; background: #f3f4f6; color: var(--hali-encre); font-weight: 650; }
    .pa-suffixe { position: relative; }
    .pa-suffixe input { padding-right: 46px; }
    .pa-suffixe span { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: var(--hali-discret); font-size: .8rem; font-weight: 600; }
    .assurance-choix-patient .list-group { max-width: none !important; width: 100%; border-radius: 10px; overflow: hidden; }
    @media (max-width: 1199.98px) { .pa-entete { display: none; } .pa-ligne { grid-template-columns: 1fr 1fr; } .pa-ligne > :first-child, .pa-actions { grid-column: 1 / -1; } .pa-actions { justify-content: flex-start; } }
    @media (max-width: 767.98px) { .pa-deux, .pa-trois { grid-template-columns: 1fr; } }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    <header class="hl-entete">
        <div>
            <h1>Patients assurés</h1>
            <p>Les couvertures en cours de vos patients. Ici, la saisie rapide d'une assurance <strong>individuelle</strong> ; les contrats d'entreprise et les ayants droit se gèrent dans Contrats et conventions.</p>
        </div>
        <div class="hl-entete-actions">
            @if(auth()->user()?->can('assurance.referentiel.view') && Route::has('assurance.contrats.index'))<a href="{{ route('assurance.contrats.index') }}" class="hl-bouton"><i class="fas fa-file-contract" aria-hidden="true"></i> Contrats</a>@endif
            @can('patient_insurance.create')
                <button type="button" class="hl-bouton hl-bouton-plein js-ajouter"><i class="fa fa-plus" aria-hidden="true"></i> Nouvelle assurance</button>
            @endcan
        </div>
    </header>

    @if($errors->any())<div class="hl-note hl-note-danger mb-3" role="alert"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <div class="hl-kpis">
        <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">Couvertures en cours</span><span class="hl-kpi-valeur">{{ $enCours->count() }}</span></div>
        <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">Expirent sous 30 jours</span><span class="hl-kpi-valeur" style="color:{{ $bientot ? 'var(--hali-alerte)' : 'inherit' }}">{{ $bientot }}</span><span class="hl-kpi-detail">à renouveler avec le patient</span></div>
        <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">Organismes représentés</span><span class="hl-kpi-valeur">{{ $enCours->map(fn ($x) => $x['l']->insurance_company_id)->unique()->count() }}</span></div>
    </div>

    <section class="hl-bloc">
        <div class="pa-outils">
            <input type="search" id="paRecherche" class="form-control" placeholder="Patient, numéro de carte, identifiant santé…" aria-label="Rechercher">
            <select id="paOrganisme" class="form-control" aria-label="Organisme">
                <option value="">Tous les organismes</option>
                @foreach($patientInsurances->pluck('insuranceCompany')->filter()->unique('id')->sortBy('name') as $o)<option value="{{ $o->id }}">{{ $o->name }}</option>@endforeach
            </select>
            <select id="paEtat" class="form-control" aria-label="État">
                <option value="encours">En cours</option>
                <option value="">Toutes</option>
                <option value="terminee">Terminées</option>
            </select>
        </div>

        @if($lignes->isEmpty())
            <div class="hl-vide"><i class="fas fa-user-shield" aria-hidden="true"></i>Aucune couverture enregistrée.</div>
        @else
            <div class="pa-entete" aria-hidden="true"><span>Patient</span><span>Organisme · carte</span><span>Prise en charge</span><span>Période</span><span>Plafond annuel</span><span></span></div>
            @foreach($lignes as ['l' => $l, 'etat' => [$codeEtat, $libelleEtat, $tonEtat]])
                @php
                    $b = $l->beneficiaire;
                    $adherent = $b && $b->lien === \App\Enums\Assurance\LienBeneficiaire::Adherent;
                    $contrat = $b?->adhesion?->formule?->contrat;
                    $type = match (true) {
                        ! $b => 'Ancienne saisie',
                        ! $adherent => ucfirst(mb_strtolower($b->lien->libelle())) . ' de ' . ($b->adhesion?->patient?->getFullName() ?? '—'),
                        (bool) $contrat?->entreprise => 'Contrat ' . $contrat->entreprise->nom,
                        default => 'Individuelle',
                    };
                    $modifiableIci = $adherent && ! $contrat?->entreprise;
                    $plafond = (float) $l->annual_limit;
                    $utilise = min(100, $plafond > 0 ? round((float) $l->used_amount * 100 / $plafond) : 0);
                    $donnees = ['id' => $l->id, 'patient_id' => $l->patient_id, 'patient' => $l->patient?->getFullName(), 'insurance_company_id' => $l->insurance_company_id,
                        'policy_number' => $l->policy_number, 'coverage_percentage' => (float) $l->coverage_percentage, 'start_date' => $l->start_date?->toDateString(),
                        'end_date' => $l->end_date?->toDateString(), 'annual_limit' => $l->annual_limit !== null ? (float) $l->annual_limit : null, 'notes' => $l->notes];
                @endphp
                <div class="pa-ligne {{ $codeEtat === 'terminee' ? 'est-terminee' : '' }}" data-etat="{{ $codeEtat === 'terminee' ? 'terminee' : 'encours' }}" data-organisme="{{ $l->insurance_company_id }}"
                     data-recherche="{{ mb_strtolower(($l->patient?->getFullName() ?? '') . ' ' . $l->policy_number . ' ' . $l->patient?->identifiant_national_sante . ' ' . $l->insuranceCompany?->name) }}">
                    <div style="min-width:0">
                        <span class="pa-nom">@if($voirDroits && $l->patient)<a href="{{ route('assurance.droits.show', $l->patient_id) }}" title="Voir les droits">{{ $l->patient->getFullName() }}</a>@else{{ $l->patient?->getFullName() ?? 'Patient supprimé' }}@endif</span>
                        <span class="pa-type">{{ $type }}</span>
                    </div>
                    <div style="min-width:0"><span class="pa-nom" style="font-weight:600">{{ $l->insuranceCompany?->name ?? '—' }}</span><span class="pa-sous">Carte {{ $l->policy_number }}</span></div>
                    <div><span class="pa-taux">{{ $pct($l->coverage_percentage) }}</span></div>
                    <div><span class="hl-statut {{ $tonEtat }}">{{ $libelleEtat }}</span>
                        <span class="pa-sous">{{ $l->start_date?->format('d/m/Y') }} → {{ $l->end_date?->format('d/m/Y') ?? 'sans fin' }}</span></div>
                    <div>
                        @if($plafond > 0)
                            <span class="pa-sous"><strong style="color:var(--hali-encre)">{{ $gnf($l->used_amount) }}</strong> / {{ $gnf($plafond) }} GNF</span>
                            <div class="pa-barre {{ $utilise >= 100 ? 'est-epuise' : ($utilise >= 80 ? 'est-bas' : '') }}" title="{{ $utilise }} % utilisés"><span style="width: {{ max(2, $utilise) }}%"></span></div>
                            @if($utilise >= 100)<span class="pa-sous pa-epuise">Plafond atteint : tout est à la charge du patient</span>@endif
                        @else
                            <span class="pa-sous">Illimité · {{ $gnf($l->used_amount) }} GNF utilisés</span>
                        @endif
                    </div>
                    <div class="pa-actions">
                        @if($modifiableIci)
                            @can('patient_insurance.edit')
                                <button type="button" class="pa-bouton js-modifier" data-couverture="{{ json_encode($donnees) }}" title="Modifier" aria-label="Modifier la couverture de {{ $l->patient?->getFullName() }}"><i class="fa fa-pen"></i></button>
                            @endcan
                        @elseif($voirAdhesion && $b?->adhesion)
                            <a href="{{ route('assurance.adhesions.show', $b->adhesion) }}" class="pa-bouton" title="Gérer dans le contrat" aria-label="Gérer dans le contrat"><i class="fas fa-external-link-alt"></i></a>
                        @endif
                        @can('patient_insurance.delete')
                            @if($codeEtat !== 'terminee')
                                <form method="POST" action="{{ route('insurance_patient.destroy') }}" onsubmit="return confirm('Résilier cette couverture à la date du jour ? Elle reste visible dans l\'historique des factures.');">
                                    @csrf @method('DELETE')<input type="hidden" name="id" value="{{ $l->id }}">
                                    <button type="submit" class="pa-bouton est-risque" title="Résilier" aria-label="Résilier"><i class="fas fa-ban"></i></button>
                                </form>
                            @endif
                        @endcan
                    </div>
                </div>
            @endforeach
            <div class="hl-vide" id="paAucun" hidden>Aucune couverture ne correspond.</div>
        @endif
    </section>

    {{-- ================= Fenêtre unique : ajout et modification ================= --}}
    @canany(['patient_insurance.create', 'patient_insurance.edit'])
    <div class="modal fade pa-modal" id="paModal" tabindex="-1" aria-hidden="true" aria-labelledby="paTitre">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form class="modal-content" method="POST" id="paForm" data-ajout="{{ route('insurance_patient.store') }}" data-modif="{{ route('insurance_patient.update') }}" action="{{ route('insurance_patient.store') }}">
                @csrf
                <input type="hidden" name="_method" id="paMethode" value="POST">
                <input type="hidden" name="id" id="paId">
                <div class="modal-header"><h5 class="modal-title" id="paTitre">Nouvelle assurance individuelle</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
                <div class="modal-body">
                    <div id="paChoixPatient">
                        @include('assurance.partials.choix-patient', ['id' => 'paPatient', 'libelle' => 'Patient', 'url' => route('insurance_patient.patients.recherche')])
                    </div>
                    <div id="paPatientFixe" hidden>
                        <label>Patient</label><div class="pa-patient-fixe" id="paPatientNom"></div>
                        <input type="hidden" name="patient_id" id="paPatientId" disabled>
                    </div>
                    <div class="pa-deux">
                        <div><label for="paOrganismeChoix">Organisme payeur *</label>
                            <select id="paOrganismeChoix" name="insurance_company_id" class="form-control" required>
                                <option value="">Choisir…</option>
                                @foreach($insuranceCompanies as $o)<option value="{{ $o->id }}" data-taux="{{ (float) $o->default_coverage_percentage }}">{{ $o->name }}</option>@endforeach
                            </select></div>
                        <div><label for="paCarte">N° de carte *</label><input id="paCarte" name="policy_number" class="form-control" maxlength="100" required></div>
                    </div>
                    <div class="pa-trois">
                        <div><label for="paTaux">Prise en charge *</label><div class="pa-suffixe"><input id="paTaux" type="number" name="coverage_percentage" class="form-control" min="0" max="100" step="0.01" required><span>%</span></div></div>
                        <div><label for="paDebut">Du *</label><input id="paDebut" type="date" name="start_date" class="form-control" required></div>
                        <div><label for="paFin">au</label><input id="paFin" type="date" name="end_date" class="form-control"></div>
                    </div>
                    <div class="pa-deux">
                        <div><label for="paPlafond">Plafond annuel</label><div class="pa-suffixe"><input id="paPlafond" type="number" name="annual_limit" class="form-control" min="0" step="1" placeholder="Illimité"><span>GNF</span></div></div>
                        <div><label for="paNotes">Notes</label><input id="paNotes" name="notes" class="form-control" placeholder="Accord préalable, pièces à fournir…"></div>
                    </div>
                    <p class="hl-note hl-note-info mb-0" id="paAideModif" hidden><i class="fas fa-info-circle" aria-hidden="true"></i> <span>Pour changer d'organisme, résiliez cette couverture puis créez-en une nouvelle.</span></p>
                </div>
                <div class="modal-footer"><button type="button" class="hl-bouton" data-bs-dismiss="modal">Annuler</button><button type="submit" class="hl-bouton hl-bouton-plein" id="paValider">Enregistrer</button></div>
            </form>
        </div>
    </div>
    @endcanany
</div></div>
@endsection

@section('script')
@include('assurance.partials.choix-patient-script')
<script>
(function () {
    var modal = document.getElementById('paModal'), form = document.getElementById('paForm');
    function ouvrir(c) {
        if (!form) return;
        var modif = !!c;
        form.action = modif ? form.dataset.modif : form.dataset.ajout;
        document.getElementById('paMethode').value = modif ? 'PUT' : 'POST';
        document.getElementById('paTitre').textContent = modif ? 'Modifier la couverture' : 'Nouvelle assurance individuelle';
        document.getElementById('paAideModif').hidden = !modif;
        // Ajout : recherche du patient. Modification : patient fixé (on ne déplace pas une couverture).
        document.getElementById('paChoixPatient').hidden = modif;
        document.getElementById('paPatientFixe').hidden = !modif;
        document.querySelectorAll('#paChoixPatient input').forEach(function (i) { i.disabled = modif; });
        var fixe = document.getElementById('paPatientId'); fixe.disabled = !modif;
        c = c || {};
        document.getElementById('paId').value = c.id || '';
        fixe.value = c.patient_id || '';
        document.getElementById('paPatientNom').textContent = c.patient || '';
        document.getElementById('paOrganismeChoix').value = c.insurance_company_id || '';
        document.getElementById('paCarte').value = c.policy_number || '';
        document.getElementById('paTaux').value = c.coverage_percentage != null ? c.coverage_percentage : '';
        document.getElementById('paDebut').value = c.start_date || new Date().toISOString().slice(0, 10);
        document.getElementById('paFin').value = c.end_date || '';
        document.getElementById('paPlafond').value = c.annual_limit != null ? c.annual_limit : '';
        document.getElementById('paNotes').value = c.notes || '';
        bootstrap.Modal.getOrCreateInstance(modal).show();
    }
    document.querySelectorAll('.js-ajouter').forEach(function (b) { b.addEventListener('click', function () { ouvrir(null); }); });
    document.querySelectorAll('.js-modifier').forEach(function (b) { b.addEventListener('click', function () { ouvrir(JSON.parse(b.dataset.couverture)); }); });

    // L'organisme propose son taux par défaut si le champ est vide.
    var organisme = document.getElementById('paOrganismeChoix');
    if (organisme) organisme.addEventListener('change', function () {
        var taux = document.getElementById('paTaux'), opt = organisme.selectedOptions[0];
        if (opt && opt.dataset.taux && !taux.value) taux.value = opt.dataset.taux;
    });
    if (form) form.addEventListener('submit', function (e) {
        var ajout = document.getElementById('paMethode').value === 'POST';
        var choisi = form.querySelector('#paChoixPatient .js-patient-id');
        if (ajout && choisi && !choisi.value) { e.preventDefault(); alert('Choisissez le patient dans la liste des résultats.'); return; }
        document.getElementById('paValider').disabled = true;
    });

    // Filtres : texte, organisme, état.
    var champ = document.getElementById('paRecherche'), filtreOrg = document.getElementById('paOrganisme'), filtreEtat = document.getElementById('paEtat');
    function filtrer() {
        var t = (champ.value || '').trim().toLowerCase(), o = filtreOrg.value, e = filtreEtat.value, n = 0;
        document.querySelectorAll('.pa-ligne').forEach(function (l) {
            var ok = (!t || l.dataset.recherche.indexOf(t) !== -1) && (!o || l.dataset.organisme === o) && (!e || l.dataset.etat === e);
            l.hidden = !ok; if (ok) n++;
        });
        var vide = document.getElementById('paAucun'); if (vide) vide.hidden = n > 0;
    }
    [champ, filtreOrg, filtreEtat].forEach(function (el) { if (el) el.addEventListener(el.tagName === 'INPUT' ? 'input' : 'change', filtrer); });
    if (champ) filtrer();

    @if($errors->any())
        if (form) bootstrap.Modal.getOrCreateInstance(modal).show();
    @endif
})();
</script>
@endsection
